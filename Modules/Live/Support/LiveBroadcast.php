<?php

namespace Modules\Live\Support;

/**
 * Publishes a live event without letting a socket outage fail the request.
 *
 * Live events broadcast inline, so while Reverb is restarting — unattended
 * upgrades bounce supervisor on some mornings — the publish throws and would
 * turn a chat message that is already saved into a 500. The write is what
 * matters: a client that misses the push picks the change up on its next fetch
 * (the chat has after_id for exactly that).
 *
 * A refused connection arrives as a BroadcastException ("cURL error 7"). The
 * catch is wider than that on purpose: building the payload in broadcastWith()
 * can fail as well, and neither is worth failing a request whose write already
 * went through. Both still reach report().
 */
final class LiveBroadcast
{
    public static function send(object $event): void
    {
        try {
            event($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
