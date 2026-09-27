<?php

namespace Modules\Live\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Live\Events\LiveChatMessageRemoved;
use Modules\Live\Events\LiveChatMessageSent;
use Modules\Live\Http\Entities\LiveChatBan;
use Modules\Live\Http\Entities\LiveChatMessage;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Support\LiveBroadcast;
use Modules\User\Http\Entities\User;

class LiveChatService
{
    /**
     * Recent history for someone who just opened the stream. Ordered oldest
     * first so the client can append without re-sorting; `after_id` lets a
     * client that lost its socket catch up instead of reloading everything.
     */
    public function history(LiveStream $stream, ?int $afterId = null)
    {
        $limit = (int) config('live.chat.history_limit', 50);

        $query = $stream->messages()->orderByDesc('id')->limit($limit);

        if ($afterId) {
            return $stream->messages()
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        return $query->get()->sortBy('id')->values();
    }

    /**
     * @return array{ok: bool, message?: LiveChatMessage, error?: string, status?: int}
     */
    public function send(LiveStream $stream, User $user, string $body): array
    {
        if (! $stream->isLive()) {
            return ['ok' => false, 'error' => 'Chat is closed for this stream.', 'status' => 409];
        }

        $ban = $this->activeBan($stream, $user->id);

        if ($ban) {
            return [
                'ok' => false,
                'error' => $ban->is_blocked
                    ? 'You are blocked from this chat.'
                    : 'You are muted for now.',
                'status' => 403,
            ];
        }

        $throttleKey = 'live:chat:throttle:' . $stream->id . ':' . $user->id;
        $throttleSeconds = (int) config('live.chat.throttle_seconds', 2);

        if ($throttleSeconds > 0 && Cache::has($throttleKey)) {
            return ['ok' => false, 'error' => 'You are writing too fast.', 'status' => 429];
        }

        $message = $stream->messages()->create([
            'user_id' => $user->id,
            'author_name' => (string) ($user->name ?: 'İstifadəçi'),
            'is_admin' => $this->isAdmin($user),
            'body' => trim($body),
        ]);

        if ($throttleSeconds > 0) {
            Cache::put($throttleKey, true, $throttleSeconds);
        }

        LiveBroadcast::send(new LiveChatMessageSent($message));

        return ['ok' => true, 'message' => $message];
    }

    public function remove(LiveChatMessage $message, ?int $byUserId = null): void
    {
        $message->deleted_by = $byUserId;
        $message->save();
        $message->delete();

        LiveBroadcast::send(new LiveChatMessageRemoved($message->live_stream_id, $message->id));
    }

    /** Silences a viewer for a while; the mute lifts itself. */
    public function mute(LiveStream $stream, int $userId, int $minutes, ?string $reason = null): LiveChatBan
    {
        return LiveChatBan::updateOrCreate(
            ['live_stream_id' => $stream->id, 'user_id' => $userId],
            [
                'muted_until' => now()->addMinutes(max(1, $minutes)),
                'is_blocked' => false,
                'reason' => $reason,
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * A block stays until it is lifted. Passing no stream blocks the viewer
     * from every stream rather than just the current one.
     */
    public function block(?LiveStream $stream, int $userId, ?string $reason = null): LiveChatBan
    {
        return LiveChatBan::updateOrCreate(
            ['live_stream_id' => $stream?->id, 'user_id' => $userId],
            [
                'muted_until' => null,
                'is_blocked' => true,
                'reason' => $reason,
                'created_by' => auth()->id(),
            ]
        );
    }

    public function lift(?LiveStream $stream, int $userId): void
    {
        LiveChatBan::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($stream) {
                $query->whereNull('live_stream_id');

                if ($stream) {
                    $query->orWhere('live_stream_id', $stream->id);
                }
            })
            ->delete();
    }

    /** A global block outranks a per-stream one, so both are checked. */
    public function activeBan(LiveStream $stream, int $userId): ?LiveChatBan
    {
        return LiveChatBan::query()
            ->where('user_id', $userId)
            ->where(fn ($query) => $query
                ->whereNull('live_stream_id')
                ->orWhere('live_stream_id', $stream->id))
            ->get()
            ->first(fn (LiveChatBan $ban) => $ban->isActive());
    }

    /**
     * Every restriction that applies in this stream right now — blocks for this
     * stream or for all of them, and mutes that have not run out yet.
     */
    public function restrictions(LiveStream $stream)
    {
        return LiveChatBan::query()
            ->where(fn ($query) => $query
                ->whereNull('live_stream_id')
                ->orWhere('live_stream_id', $stream->id))
            ->get()
            ->filter(fn (LiveChatBan $ban) => $ban->isActive())
            ->values();
    }

    /**
     * The badge means the shop is speaking, so it goes to anyone who may run a
     * stream from the panel — not only holders of the literal 'admin' role.
     */
    private function isAdmin(User $user): bool
    {
        try {
            return $user->hasRole('admin') || $user->can('manage live');
        } catch (\Throwable) {
            return false;
        }
    }
}
