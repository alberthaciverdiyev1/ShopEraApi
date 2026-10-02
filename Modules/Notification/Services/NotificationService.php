<?php

namespace Modules\Notification\Services;

use App\Jobs\SendUserPushJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Notification\Entities\Notification;
use Modules\Notification\Http\Resources\NotificationResource;

class NotificationService
{
    private Notification $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    public function add(array $data): Notification
    {
        $notification = DB::transaction(function () use ($data) {
            $notification = $this->model->create([
                'title' => isset($data['title']) ? Str::lower($data['title']) : '',
                'body' => isset($data['body']) ? Str::lower($data['body']) : '',
                'user_id' => $data['user_id'] ?? null,
                'all' => $data['all'] ?? false,
                'source' => $data['source'] ?? 'system',
                'data' => $data['data'] ?? null,
                'icon' => $data['icon'] ?? null,
                'image' => $data['image'] ?? null,
            ]);

            if (! empty($data['user_id'])) {
                $notification->users()->attach($data['user_id']);
            }

            return $notification;
        });

        // The row is stored first, then the device is told. Callers that send
        // the push themselves pass 'push' => false to avoid a double delivery.
        $this->queuePush($data);

        return $notification;
    }

    /**
     * Hands the stored notification to the user's devices.
     *
     * Store-side notifications used to end here — written to the database and
     * never pushed — so a merchant only learned about a new order by opening
     * the app.
     */
    private function queuePush(array $data): void
    {
        if (empty($data['user_id']) || ($data['push'] ?? true) === false) {
            return;
        }

        try {
            SendUserPushJob::dispatch(
                (int) $data['user_id'],
                (string) ($data['title'] ?? ''),
                (string) ($data['body'] ?? ''),
                is_array($data['data'] ?? null) ? $data['data'] : [],
                $data['icon'] ?? null,
                $data['image'] ?? null,
            )->afterCommit();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function addMultiple(array $notificationData, array $userIds, bool $all = false): bool
    {
        try {
            DB::beginTransaction();

            $notification = $this->model->create([
                'title' => isset($notificationData['title']) ? Str::lower($notificationData['title']) : '',
                'body' => isset($notificationData['body']) ? Str::lower($notificationData['body']) : '',
                'user_id' => $all ? null : ($notificationData['user_id'] ?? null),
                'all' => $all,
                'source' => $notificationData['source'] ?? 'system',
                'data' => $notificationData['data'] ?? null,
                'icon' => $notificationData['icon'] ?? null,
                'url' => $notificationData['url'] ?? null,
                'image' => $notificationData['image'] ?? null,
            ]);

            if (! $all && ! empty($userIds)) {
                $notification->users()->attach($userIds);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Notification addMultiple failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Notification list
     */
    public function listAdmin($request)
    {
        $filters = $request->all();

        $query = $this->model
            ->with(['users'])
            ->orderBy('created_at', 'desc');

        // The admin panel lists what staff composed, not the order updates the
        // application sends to customers on its own. ?source=all brings those
        // back when someone is diagnosing delivery.
        if (($filters['source'] ?? null) !== 'all') {
            $query->where('source', $filters['source'] ?? 'admin');
        }

        if (! empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id'])
                    ->orWhereHas('users', function ($q2) use ($filters) {
                        $q2->where('users.id', $filters['user_id']);
                    });
            });
        }

        if (! empty($filters['search'])) {
            $search = Str::lower($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $notifications = $query->paginate(20);

        return responseHelper(__('Notifications retrieved successfully.'),
            200,
            NotificationResource::collection($notifications)
        );
    }

    public function list($request)
    {
        $filters = $request->all();

        $query = $this->model
            ->orderBy('created_at', 'desc');

        $userId = auth()->id();
        $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('all', true)
                ->orWhereHas('users', fn ($q2) => $q2->where('users.id', $userId));
        });

        $notifications = $query->paginate(20);

        return responseHelper(__('Notifications retrieved successfully.'),
            200,
            NotificationResource::collection($notifications)
        );
    }

    public function delete(int $notificationId)
    {
        try {
            DB::beginTransaction();

            $notification = $this->model->find($notificationId);

            if (! $notification) {
                return false;
            }

            $notification->users()->detach();

            $notification->delete();

            DB::commit();

            return responseHelper(__('Notifications deleted successfully.'),
                200,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Notification delete failed', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
