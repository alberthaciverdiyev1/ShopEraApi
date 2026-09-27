<?php

namespace App\Services\Starex;

use App\Enums\OrderStatus as OrderStatusEnum;
use App\Helpers\PhoneHelper;
use App\Jobs\SendOrderStatusNotificationJob;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Delivery\Http\Entities\City;
use Modules\Delivery\Http\Entities\Delivery;
use Modules\Delivery\Http\Entities\PickupPoint;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderStatus;
use Modules\Order\Http\Entities\StarexShipment;
use Modules\Order\Http\Entities\StarexShipmentEvent;
use RuntimeException;

class StarexShipmentService
{
    public function __construct(private readonly StarexClient $client)
    {
    }

    public function createForOrder(int $orderId): void
    {
        if (!config('services.starex.enabled', false)) {
            return;
        }

        $order = Order::with(['items.product', 'user', 'address', 'latestStatus'])->findOrFail($orderId);

        if (!$this->shouldCreateShipment($order)) {
            return;
        }

        $shipment = StarexShipment::firstOrCreate(
            ['order_id' => $order->id],
            ['sync_status' => 'pending']
        );

        if ($shipment->tracking_number || $shipment->sync_status === 'cancelled') {
            return;
        }

        $payload = null;

        try {
            $payload = $this->buildCreatePayload($order);
            $shipment->update([
                'delivery_type' => $payload['delivery_type'],
                'sync_status' => 'processing',
                'last_error' => null,
            ]);

            $response = $this->client->createPackage($payload);
            $data = $response['data'] ?? $response;
            $trackingNumber = $data['tracking_number'] ?? null;

            if (!$trackingNumber) {
                throw new RuntimeException('Starex create response did not contain a tracking number.');
            }

            $shipment->update([
                'package_id' => $data['package_id'] ?? null,
                'tracking_number' => $trackingNumber,
                'sticker' => $data['sticker'] ?? null,
                'sync_status' => 'created',
                'sent_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $e) {
            $lastError = $this->starexErrorMessage($e, $payload);

            $shipment->update([
                'sync_status' => 'failed',
                'last_error' => $lastError,
            ]);

            Log::error('Starex shipment could not be created.', [
                'order_id' => $order->id,
                'error' => $lastError,
                'starex_context' => $this->starexPayloadContext($payload),
            ]);

            throw $e;
        }
    }

    public function cancelForOrder(int $orderId): void
    {
        $shipment = StarexShipment::where('order_id', $orderId)->first();

        if (!$shipment || !$shipment->tracking_number || $shipment->sync_status === 'cancelled') {
            return;
        }

        try {
            $this->client->deletePackage($shipment->tracking_number);
            $shipment->update([
                'sync_status' => 'cancelled',
                'cancelled_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $e) {
            $shipment->update([
                'sync_status' => 'cancel_failed',
                'last_error' => $e->getMessage(),
            ]);

            Log::error('Starex shipment could not be cancelled.', [
                'order_id' => $orderId,
                'tracking_number' => $shipment->tracking_number,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function handleWebhook(array $payload): void
    {
        $trackingNumber = trim((string) ($payload['tracking_number'] ?? ''));
        $status = trim((string) ($payload['status'] ?? ''));
        $eventId = filled($payload['event_id'] ?? null) ? (string) $payload['event_id'] : null;

        if (!$trackingNumber || !$status) {
            throw new RuntimeException('Starex webhook requires tracking_number and status.');
        }

        DB::transaction(function () use ($payload, $trackingNumber, $status, $eventId) {
            if ($eventId && StarexShipmentEvent::where('event_id', $eventId)->exists()) {
                return;
            }

            $shipment = StarexShipment::where('tracking_number', $trackingNumber)
                ->lockForUpdate()
                ->first();

            StarexShipmentEvent::create([
                'starex_shipment_id' => $shipment?->id,
                'event_id' => $eventId,
                'tracking_number' => $trackingNumber,
                'status' => $status,
                'event_date' => $this->parseDate($payload['event_date'] ?? null),
                'payload' => $payload,
            ]);

            if (!$shipment) {
                Log::warning('Starex webhook tracking number was not found locally.', [
                    'tracking_number' => $trackingNumber,
                    'status' => $status,
                    'event_id' => $eventId,
                ]);

                return;
            }

            $shipment->update([
                'external_status' => $status,
                'last_event_at' => $this->parseDate($payload['event_date'] ?? null) ?? now(),
            ]);

            if ($status === 'delivered') {
                $this->markOrderDelivered($shipment->order_id);
            }
        });
    }

    public function reconcileActiveShipments(): int
    {
        if (!config('services.starex.enabled', false)) {
            return 0;
        }

        $count = 0;

        StarexShipment::query()
            ->whereNotNull('tracking_number')
            ->whereNotIn('sync_status', ['cancelled'])
            ->where(function ($query) {
                $query->whereNull('external_status')->orWhere('external_status', '!=', 'delivered');
            })
            ->chunkById(100, function ($shipments) use (&$count) {
                foreach ($shipments as $shipment) {
                    try {
                        $response = $this->client->packageHistory($shipment->tracking_number);
                        $history = $response['data'] ?? $response;
                        $latest = collect(is_array($history) ? $history : [])
                            ->sortByDesc(fn(array $item) => $item['created_at'] ?? '')
                            ->first();

                        if (!empty($latest['status'])) {
                            $this->handleWebhook([
                                'tracking_number' => $shipment->tracking_number,
                                'status' => $latest['status'],
                                'event_date' => $latest['created_at'] ?? null,
                                'event_id' => implode(':', [
                                    'reconcile',
                                    $shipment->tracking_number,
                                    $latest['status'],
                                    $latest['created_at'] ?? 'unknown-date',
                                ]),
                            ]);
                            $count++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Starex shipment reconciliation failed.', [
                            'shipment_id' => $shipment->id,
                            'tracking_number' => $shipment->tracking_number,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    public function syncPickupPoints(bool $createMissing = false): array
    {
        $response = $this->client->pickupPoints();
        $points = $response['data'] ?? $response;
        $localPoints = PickupPoint::withTrashed()->get();
        $summary = [
            'updated' => 0,
            'created_inactive' => 0,
            'possible_matches' => [],
            'unmatched' => [],
        ];

        foreach (is_array($points) ? $points : [] as $point) {
            $externalId = $point['id'] ?? null;

            if (!$externalId) {
                continue;
            }

            $externalName = (string) ($point['name'] ?? "Starex PUDO {$externalId}");
            $externalAddress = (string) ($point['address'] ?? '');
            $pickupPoint = $localPoints->first(
                fn(PickupPoint $localPoint) => (string) $localPoint->starex_delivery_point_id === (string) $externalId
            );

            if ($pickupPoint) {
                $pickupPoint->fill([
                    'name' => $externalName,
                    'address' => $externalAddress,
                ])->save();
                $summary['updated']++;

                continue;
            }

            $possibleMatches = $localPoints
                ->filter(fn(PickupPoint $localPoint) => !$localPoint->starex_delivery_point_id)
                ->filter(function (PickupPoint $localPoint) use ($externalName, $externalAddress) {
                    $sameName = $this->normalizeLocationText($localPoint->name) === $this->normalizeLocationText($externalName);
                    $sameAddress = $externalAddress
                        && $this->normalizeLocationText($localPoint->address) === $this->normalizeLocationText($externalAddress);

                    return $sameName || $sameAddress;
                })
                ->map(fn(PickupPoint $localPoint) => [
                    'local_id' => $localPoint->id,
                    'name' => $localPoint->name,
                    'address' => $localPoint->address,
                    'deleted' => $localPoint->trashed(),
                ])
                ->values()
                ->all();

            if ($possibleMatches) {
                $summary['possible_matches'][] = [
                    'starex_delivery_point_id' => $externalId,
                    'name' => $externalName,
                    'address' => $externalAddress,
                    'local_candidates' => $possibleMatches,
                ];

                continue;
            }

            if (!$createMissing) {
                $summary['unmatched'][] = [
                    'starex_delivery_point_id' => $externalId,
                    'name' => $externalName,
                    'address' => $externalAddress,
                ];

                continue;
            }

            $deliveryTime = (string) config('services.starex.pickup_delivery_time', '2-3 gün');

            $pickupPoint = PickupPoint::create([
                'starex_delivery_point_id' => $externalId,
                'name' => $externalName,
                'address' => $externalAddress,
                'price' => 0,
                'is_active' => false,
                'delivery_time' => [
                    'az' => $deliveryTime,
                    'en' => $deliveryTime,
                    'ru' => $deliveryTime,
                    'tr' => $deliveryTime,
                ],
            ]);

            $localPoints->push($pickupPoint);
            $summary['created_inactive']++;
        }

        return $summary;
    }

    private function shouldCreateShipment(Order $order): bool
    {
        // Fast delivery is driven by the shop's own courier, so those orders
        // never go to Starex — the client asked for this after packages were
        // being created for deliveries their own driver was already making.
        if (!in_array($order->address_type, ['STANDARD', 'PICKUP_POINT'], true)) {
            return false;
        }

        return $order->latestStatus?->status === OrderStatusEnum::PROCESSING;
    }

    private function buildCreatePayload(Order $order): array
    {
        $address = $order->address;

        if (!$address && in_array($order->address_type, ['STANDARD', 'STANDARD_FAST'], true)) {
            $address = \Modules\User\Http\Entities\Address::find($order->address_type_id);
        }

        $payload = [
            'receiver_name' => $this->cleanStarexText($address?->full_name ?: $order->user?->name, 100),
            'receiver_phone' => $this->starexPhone($address?->contact_number ?: $order->user?->phone),
            'receiver_email' => $order->user?->email,
            'vendor_tracking_number' => $order->transaction_id,
            'quantity' => max(1, (int) $order->items->sum('quantity')),
            'price' => (float) $order->total_price,
            'weight' => $this->calculateWeight($order),
            'currency' => config('services.starex.currency', 'azn'),
            'product_name' => $this->productNames($order),
            'note' => $this->cleanStarexText($order->note, 250),
            'has_documents' => false,
            'cod' => false,
            'type' => config('services.starex.package_type', 'general_goods'),
        ];

        if ($order->address_type === 'PICKUP_POINT') {
            $pickupPoint = PickupPoint::withTrashed()->find($order->address_type_id);

            if (!$pickupPoint?->starex_delivery_point_id) {
                throw new RuntimeException('Selected pickup point is not mapped to a Starex PUDO point.');
            }

            $payload['delivery_type'] = config('services.starex.pudo_delivery_type', 'pudo_delivery');
            $payload['delivery_point_id'] = $pickupPoint->starex_delivery_point_id;
        } else {
            if (!$address) {
                throw new RuntimeException('Order address could not be resolved for Starex delivery.');
            }

            $city = $this->resolveCity($address->city);
            $delivery = $this->resolveDelivery($address->city, $city);

            if (!$delivery) {
                throw new RuntimeException("Starex city mapping was not found for address city: {$address->city}.");
            }

            $regionId = $delivery->starex_region_id ?: config('services.starex.default_region_id');

            if (!$regionId) {
                throw new RuntimeException("Starex region ID is not configured for city: {$delivery->city_name}.");
            }

            $cityLabel = $city?->name ?: $delivery->city_name ?: $address->city;

            $payload['delivery_type'] = config('services.starex.home_delivery_type', 'home_delivery');
            $payload['receiver_address'] = $this->cleanStarexText(collect([
                $cityLabel,
                $address->town_village_district,
                $address->street_building_number,
                $address->unit_floor_apartment,
            ])->filter()->implode(', '), 250);
            $payload['region_id'] = (int) $regionId;
        }

        return array_filter($payload, fn($value) => $value !== null && $value !== '');
    }

    private function calculateWeight(Order $order): float
    {
        $defaultWeight = max(0.001, (float) config('services.starex.default_weight', 1));

        return round((float) $order->items->sum(function ($item) use ($defaultWeight) {
            return ((float) ($item->product?->weight ?: $defaultWeight)) * max(1, (int) $item->quantity);
        }), 3);
    }

    private function productNames(Order $order): string
    {
        $names = $order->items
            ->groupBy(fn($item) => (string) ($item->product_id ?? $item->id))
            ->map(function ($items) {
                $item = $items->first();
                $quantity = max(1, (int) $items->sum('quantity'));
                $sku = trim((string) ($item->product?->sku ?? ''));
                $title = $this->cleanStarexText(
                    $item->product?->getTranslation('title', 'az', false),
                    50
                );
                $name = $sku ?: ($title ?: "Product {$item->product_id}");

                return "{$name} x{$quantity}";
            })
            ->filter()
            ->implode(', ');

        return $this->cleanStarexText($names ?: "TeymurStore order #{$order->id}", 190);
    }

    private function markOrderDelivered(int $orderId): void
    {
        $order = Order::with(['latestStatus', 'items'])->find($orderId);

        if (!$order || in_array($order->latestStatus?->status, [
            OrderStatusEnum::DELIVERED,
            OrderStatusEnum::RETURNED,
            OrderStatusEnum::CANCELLED,
        ], true)) {
            return;
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::DELIVERED->value,
        ]);

        try {
            $notification = OrderStatusEnum::DELIVERED->getNotificationMessage($order->id);

            SendOrderStatusNotificationJob::dispatch(
                $order->user_id,
                $order->id,
                OrderStatusEnum::DELIVERED->name,
                $notification['title'],
                $notification['body'],
                [
                    'order_id' => (string) $order->id,
                    'type' => 'WRITE_REVIEW',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'product_id' => (string) ($order->items->first()?->product_id),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Starex delivered notification could not be queued.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeLocationText(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[^\pL\pN]+/u', '')
            ->toString();
    }

    private function cleanStarexText(?string $value, int $limit): string
    {
        return Str::of((string) $value)
            ->replaceMatches('/[^\pL\pN\s\-_.#,]/u', ' ')
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->limit($limit, '')
            ->toString();
    }

    private function starexPhone(?string $phone): string
    {
        $normalized = PhoneHelper::normalize($phone);

        if (strlen($normalized) === 10 && str_starts_with($normalized, '0')) {
            return '+994' . substr($normalized, 1);
        }

        if (strlen($normalized) === 12 && str_starts_with($normalized, '994')) {
            return '+' . $normalized;
        }

        return $normalized;
    }

    private function resolveCity(?string $value): ?City
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return City::withTrashed()
            ->where(function ($query) use ($value) {
                $query
                    ->whereRaw('LOWER(key) = ?', [mb_strtolower($value)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
            })
            ->first();
    }

    private function resolveDelivery(?string $addressCity, ?City $city): ?Delivery
    {
        $candidates = collect([$addressCity, $city?->key, $city?->name])
            ->filter()
            ->map(fn($value) => $this->normalizeLocationText($value))
            ->filter()
            ->unique()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return Delivery::withTrashed()
            ->get()
            ->first(fn(Delivery $delivery) => $candidates->contains(
                $this->normalizeLocationText($delivery->city_name)
            ));
    }

    private function starexErrorMessage(\Throwable $e, ?array $payload): string
    {
        $message = $e->getMessage();

        if ($e instanceof RequestException && $e->response) {
            $body = $e->response->json() ?? $e->response->body();
            $apiMessage = is_array($body)
                ? json_encode($body, JSON_UNESCAPED_UNICODE)
                : Str::limit((string) $body, 300);

            $message = "Starex API {$e->response->status()}: {$apiMessage}";
        }

        $context = $this->starexPayloadContext($payload);

        return $context ? "{$message} | {$context}" : $message;
    }

    private function starexPayloadContext(?array $payload): ?string
    {
        if (!$payload) {
            return null;
        }

        return collect([
            'delivery_type' => $payload['delivery_type'] ?? null,
            'region_id' => $payload['region_id'] ?? null,
            'delivery_point_id' => $payload['delivery_point_id'] ?? null,
            'receiver_phone' => $payload['receiver_phone'] ?? null,
            'receiver_address' => $payload['receiver_address'] ?? null,
            'product_name' => isset($payload['product_name'])
                ? Str::limit((string) $payload['product_name'], 120, '')
                : null,
        ])
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode(', ');
    }
}
