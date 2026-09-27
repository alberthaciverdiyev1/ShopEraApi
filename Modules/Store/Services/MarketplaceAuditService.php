<?php

namespace Modules\Store\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Store\Http\Entities\MarketplaceAuditLog;

/**
 * Records who changed what on the marketplace. Every write here is best-effort:
 * an audit failure must never abort the operation it was describing.
 */
class MarketplaceAuditService
{
    public function log(string $action, ?Model $subject = null, array $changes = []): void
    {
        try {
            MarketplaceAuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'changes' => $changes ?: null,
                'ip' => request()?->ip(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Logs only the attributes that actually changed, as before/after pairs.
     */
    public function logDiff(string $action, Model $subject, array $before, array $after): void
    {
        $changes = [];
        foreach ($after as $key => $value) {
            $old = $before[$key] ?? null;
            if ((string) $old !== (string) $value) {
                $changes[$key] = ['from' => $old, 'to' => $value];
            }
        }

        if ($changes) {
            $this->log($action, $subject, $changes);
        }
    }
}
