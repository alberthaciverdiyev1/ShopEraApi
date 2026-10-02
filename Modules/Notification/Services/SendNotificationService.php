<?php

namespace Modules\Notification\Services;

use App\Support\TenantContext;
use App\Jobs\SendNotificationChunkJob;
use Google\Client as GoogleClient;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Notification\Entities\NotificationToken;

class SendNotificationService
{
    /**
     * FCM retired its batch endpoint, so a broadcast is one HTTP call per
     * device. They go out this many at a time, multiplexed as HTTP/2 streams
     * over a handful of connections.
     *
     * The previous 25-at-a-time HTTP/1.1 pools opened a fresh TLS connection
     * for every request and managed 23 devices a second — a full broadcast
     * took about 20 minutes. Measured on production with validate_only: about
     * 650 devices a second, so every device in roughly 15 seconds.
     */
    private const PARALLEL_REQUESTS = 100;

    /**
     * Devices per queued broadcast job. At the rate above a job of this size
     * takes under a second; smaller jobs only added queue round-trips.
     */
    public const BROADCAST_CHUNK = 500;

    // Both may be absent in environments without Firebase configured; the
    // send paths then fail with a clear message instead of a type error.
    private ?string $projectId = null;

    private ?string $serviceAccountPath = null;

    private NotificationToken $tokenModel;

    private NotificationService $notificationService;

    public function __construct(NotificationToken $tokenModel, NotificationService $notificationService)
    {
        $this->projectId = config('services.fcm.project_id') ?: null;
        $serviceAccount = config('services.fcm.service_account_path');
        $this->serviceAccountPath = $serviceAccount ? storage_path($serviceAccount) : null;
        $this->tokenModel = $tokenModel;
        $this->notificationService = $notificationService;
    }

    //    public function sendToOneUser(string $deviceToken, string $title, string $body, ?string $icon = null, array $data = [], bool $dryRun = false, $imageUrl = null, $url = null): array
    //    {
    //        $formattedData = $this->convertDataToStrings($data);
    //
    //        $message = [
    //            'token' => $deviceToken,
    //            'notification' => [
    //                'title' => $title,
    //                'body' => $body,
    //            ],
    //        ];
    //
    //        if ($imageUrl) {
    //            $message['notification']['image'] = $imageUrl;
    //        }
    //        if ($url) {
    //            $message['notification']['url'] = $url;
    //        }
    //
    //        if (!empty($formattedData)) {
    //            $message['data'] = $formattedData;
    //        }
    //
    //        if ($dryRun) {
    //            return $this->sendV1Request(['message' => $message, 'validate_only' => true]);
    //        }
    //
    //        return $this->sendV1Request(['message' => $message]);
    //    }

    public function sendToOneUser(string $deviceToken, string $title, string $body, ?string $icon = null, array $data = [], bool $dryRun = false, $imageUrl = null, $url = null): array
    {
        return $this->sendV1Request(
            $this->buildPayload($deviceToken, $title, $body, $data, $dryRun, $imageUrl, $url)
        );
    }

    /**
     * The message body FCM expects for one device.
     */
    private function buildPayload(string $deviceToken, string $title, string $body, array $data, bool $dryRun, $imageUrl = null, $url = null): array
    {
        $formattedData = $this->convertDataToStrings($data);
        $formattedData['title'] ??= $title;
        $formattedData['body'] ??= $body;

        if ($url) {
            $formattedData['url'] = $url;
        }

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $formattedData,
            'android' => [
                'priority' => 'HIGH',
                // No click_action. Android resolves it as an activity action
                // and no build of the app has ever declared
                // FLUTTER_NOTIFICATION_CLICK, so every tap on a single
                // notification went nowhere (START_INTENT_NOT_RESOLVED).
                // Without it the SDK opens the app's launcher activity and
                // hands the message to getInitialMessage/onMessageOpenedApp.
                'notification' => [
                    'channel_id' => 'high_importance_channel',
                    'sound' => 'default',
                    'default_sound' => true,
                    'notification_priority' => 'PRIORITY_HIGH',
                ],
            ],
            // Without an aps block iOS has no sound to play, so every
            // notification that arrived while the app was closed was silent.
            // It only sounded when the app happened to be open, because then
            // Flutter draws the notification itself.
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'mutable-content' => 1,
                    ],
                ],
            ],
        ];

        if ($imageUrl) {
            $message['notification']['image'] = $imageUrl;
            $message['android']['notification']['image'] = $imageUrl;
            $message['apns']['fcm_options']['image'] = $imageUrl;
        }

        $payload = ['message' => $message];

        if ($dryRun) {
            $payload['validate_only'] = true;
        }

        return $payload;
    }

    /**
     * True when FCM says this registration token is gone for good.
     *
     * An uninstalled app comes back as NOT_FOUND at the top level and only
     * carries the UNREGISTERED marker inside the error details — matching on
     * the status alone missed every one of them, so dead tokens piled up
     * forever and every send wasted a request on them.
     */
    private function isDeadToken(array $result): bool
    {
        if (($result['error']['status'] ?? null) === 'INVALID_ARGUMENT') {
            return true;
        }

        foreach ($result['error']['details'] ?? [] as $detail) {
            if (($detail['errorCode'] ?? null) === 'UNREGISTERED') {
                return true;
            }
        }

        return false;
    }

    /**
     * A short label for why one send failed, e.g. "401 THIRD_PARTY_AUTH_ERROR",
     * so a batch can be summarised in a single log line.
     */
    private function failureReason(array $result, $response): string
    {
        $code = null;
        if (is_array($result['error'] ?? null)) {
            foreach ($result['error']['details'] ?? [] as $detail) {
                $code ??= $detail['errorCode'] ?? null;
            }
            $code ??= $result['error']['status'] ?? null;
        }

        $status = $response instanceof Response ? (string) $response->status() : 'transport';

        return trim($status.' '.($code ?? ''));
    }

    public function sendToMultiple(array $deviceTokens, string $title, string $body, ?string $icon = null, array $data = [], bool $dryRun = false, $imageUrl = null, $url = null): array
    {
        // The same device can be registered twice; sending twice would show
        // the notification twice.
        $deviceTokens = array_values(array_unique(array_filter($deviceTokens)));

        if (empty($deviceTokens)) {
            return ['success' => 0, 'failure' => 0, 'results' => []];
        }

        try {
            $accessToken = $this->getAccessToken();
        } catch (\Throwable $e) {
            return [
                'success' => 0,
                'failure' => count($deviceTokens),
                'results' => [],
                'error' => 'Failed to get FCM access token: '.$e->getMessage(),
            ];
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $results = [];
        $successCount = 0;
        $failureCount = 0;
        $tokensToDelete = [];

        // One pool for the whole list: its handler keeps the HTTP/2
        // connections open and the concurrency limit streams the requests
        // through them, instead of a new TLS handshake for every device.
        $responses = Http::pool(function (Pool $pool) use ($deviceTokens, $endpoint, $accessToken, $title, $body, $data, $dryRun, $imageUrl, $url) {
            foreach ($deviceTokens as $index => $token) {
                $pool->as((string) $index)
                    ->withOptions([
                        'version' => '2.0',
                        // Wait for a free stream on an open connection rather
                        // than opening another one.
                        'curl' => [CURLOPT_PIPEWAIT => true],
                    ])
                    ->withToken($accessToken)
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->post($endpoint, $this->buildPayload($token, $title, $body, $data, $dryRun, $imageUrl, $url));
            }
        }, self::PARALLEL_REQUESTS);

        $failedBy = [];
        $sampleFailure = null;

        foreach ($deviceTokens as $index => $token) {
            $response = $responses[$index] ?? null;

            if ($response instanceof Response) {
                $result = $response->json() ?? ['error' => 'Invalid response from FCM'];
            } else {
                // A transport failure, not an FCM verdict — never treat it
                // as a dead token.
                $result = ['error' => ['message' => $response instanceof \Throwable
                    ? $response->getMessage()
                    : 'Unknown FCM transport failure']];
            }

            if (isset($result['name']) && ! isset($result['error'])) {
                $successCount++;
            } else {
                $failureCount++;

                if ($this->isDeadToken($result)) {
                    // A device that uninstalled the app is an expected
                    // outcome, not an incident — removed, never logged.
                    if (! $dryRun) {
                        $tokensToDelete[] = $token;
                    }
                } else {
                    $reason = $this->failureReason($result, $response);
                    $failedBy[$reason] = ($failedBy[$reason] ?? 0) + 1;
                    $sampleFailure ??= [
                        'reason' => $reason,
                        'message' => is_array($result['error'])
                            ? ($result['error']['message'] ?? null)
                            : $result['error'],
                    ];
                }
            }

            $results[] = $result;
        }

        // One line for the whole batch, never one per device. When the APNs
        // key went invalid every iPhone failed, and logging each failure —
        // back when every error was also posted to Slack, synchronously, about
        // five a second — is what stretched a broadcast to 15-20 minutes.
        if ($failedBy !== []) {
            Log::error('FCM V1 requests failed', [
                'failed' => $failedBy,
                'sent' => count($deviceTokens),
                'sample' => $sampleFailure,
                'url' => $endpoint,
            ]);
        }

        if (! $dryRun && ! empty($tokensToDelete)) {
            $this->tokenModel->whereIn('token', $tokensToDelete)->delete();
        }

        // One line for the whole batch instead of one per device.
        if ($failureCount > 0) {
            Log::warning('FCM batch finished with failures.', [
                'sent' => count($deviceTokens),
                'success' => $successCount,
                'failure' => $failureCount,
                'unregistered_removed' => count($tokensToDelete),
            ]);
        }

        return [
            'success' => $successCount,
            'failure' => $failureCount,
            'results' => $results,
        ];
    }

    public function subscribeToTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['error' => 'No tokens provided'];
        }

        try {
            $accessToken = $this->getAccessToken();

            $url = 'https://iid.googleapis.com/iid/v1:batchAdd';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'to' => '/topics/'.$topic,
                'registration_tokens' => $tokens,
            ]);

            if (! $response->successful()) {
                Log::warning('Failed to subscribe tokens to topic', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('subscribeToTopic error: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    public function sendNotification($request, bool $dryRun = true)
    {
        try {
            $validated = $request->validated();

            $imageUrl = null;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store(TenantContext::storagePath('notifications'), 'public');
                $imageUrl = Storage::disk('public')->url($path);
            }
            $validated['image'] = $imageUrl;
            $title = $validated['title'] ?? '';
            $url = $validated['url'] ?? '';
            $body = $validated['body'] ?? '';
            $validated['icon'] = Storage::disk('public')->url('notifications/notification_icon.jpg');

            // Composed by a person in the admin panel, so it belongs in the
            // admin's own list; everything the application sends stays 'system'.
            $validated['source'] = 'admin';

            $data = $validated['data'] ?? [];
            $all = $validated['all'] ?? false;

            if ($all) {
                $this->notificationService->addMultiple($validated, [], true);

                $queuedChunks = 0;
                $queuedTokens = 0;

                $this->tokenModel
                    ->where('is_active', true)
                    ->whereNotNull('token')
                    ->select(['id', 'token'])
                    ->orderBy('id')
                    ->chunk(self::BROADCAST_CHUNK, function ($rows) use (
                        &$queuedChunks,
                        &$queuedTokens,
                        $title,
                        $body,
                        $validated,
                        $data,
                        $dryRun,
                        $imageUrl,
                        $url
                    ) {
                        $tokens = $rows
                            ->pluck('token')
                            ->filter()
                            ->values()
                            ->all();

                        if (empty($tokens)) {
                            return;
                        }

                        SendNotificationChunkJob::dispatch(
                            $tokens,
                            $title,
                            $body,
                            $validated['icon'],
                            $data,
                            $dryRun,
                            $imageUrl,
                            $url
                        );

                        $queuedChunks++;
                        $queuedTokens += count($tokens);
                    });

                return [
                    'queued' => true,
                    'success' => 0,
                    'failure' => 0,
                    'chunks' => $queuedChunks,
                    'tokens' => $queuedTokens,
                ];
            }

            if (empty($validated['users']) || ! is_array($validated['users'])) {
                return ['error' => 'No users selected'];
            }

            $tokens = $this->tokenModel
                ->whereIn('user_id', $validated['users'])
                ->where('is_active', true)
                ->pluck('token')
                ->filter()
                ->toArray();

            $this->notificationService->addMultiple($validated, $validated['users']);

            return $this->sendToMultiple($tokens, $title, $body, $validated['icon'], $data, $dryRun, $imageUrl, $url);

        } catch (\Throwable $e) {
            Log::error('SendNotification error: '.$e->getMessage());

            return ['error' => 'Failed to send notification: '.$e->getMessage()];
        }
    }

    private function sendV1Request(array $payload): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if (! $response->successful()) {
                Log::error('FCM V1 request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                ]);
            }

            return $response->json() ?? ['error' => 'Invalid response from FCM'];

        } catch (\Throwable $e) {
            Log::error('FCM V1 send request exception: '.$e->getMessage());

            return ['error' => 'Failed to send FCM request: '.$e->getMessage()];
        }
    }

    // messaging.subscribeToTopic('all_users');

    private function sendToTopic(string $topic, string $title, string $body, ?string $icon = null, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $this->convertDataToStrings($data),
                ],
            ];

            if ($icon) {
                $message['message']['notification']['image'] = $icon;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $message);

            if (! $response->successful()) {
                Log::error('FCM sendToTopic failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FCM sendToTopic exception: '.$e->getMessage());

            return ['error' => 'Failed to send topic notification: '.$e->getMessage()];
        }
    }

    private function getAccessToken(): string
    {
        if (! $this->projectId || ! $this->serviceAccountPath) {
            throw new \Exception('Firebase Cloud Messaging is not configured (FIREBASE_PROJECT_ID / FIREBASE_SERVICE_ACCOUNT_PATH).');
        }

        return Cache::remember('fcm_access_token', 55 * 60, function () {
            try {
                $client = new GoogleClient;
                $client->setAuthConfig($this->serviceAccountPath);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

                $accessToken = $client->fetchAccessTokenWithAssertion();

                if (isset($accessToken['error'])) {
                    throw new \Exception('Failed to get access token: '.$accessToken['error']);
                }

                return $accessToken['access_token'];

            } catch (\Exception $e) {
                Log::error('Failed to get FCM access token: '.$e->getMessage());
                throw $e;
            }
        });
    }

    private function convertDataToStrings(array $data): array
    {
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = is_string($value) ? $value : json_encode($value);
        }

        return $stringData;
    }

    /**
     * Spesifik bir sifariş statusu üçün bildiriş göndərir
     */
    public function sendOrderStatusNotification(int $userId, string $title, string $body, array $extraData = [])
    {
        try {
            $icon = Storage::disk('public')->url('notifications/notification_icon.jpg');

            // Stored before the device lookup: a customer without a registered
            // phone still has to find the update in the app's notification
            // list. This used to return early and lose the record entirely.
            $this->notificationService->addMultiple([
                'title' => $title,
                'body' => $body,
                'icon' => $icon,
                'data' => $extraData,
            ], [$userId]);

            $tokens = $this->tokenModel
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->pluck('token')
                ->filter()
                ->toArray();

            if (empty($tokens)) {
                Log::info("No active tokens for user ID: $userId. Push skipped.");

                return false;
            }

            $result = $this->sendToMultiple($tokens, $title, $body, $icon, $extraData, false);

            if (isset($result['error']) || ($result['failure'] ?? 0) > 0) {
                Log::warning('Order notification delivery failed for one or more devices.', [
                    'user_id' => $userId,
                    'result' => $result,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Order Notification Error: '.$e->getMessage());

            return false;
        }
    }
}
