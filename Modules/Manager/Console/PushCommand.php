<?php

namespace Modules\Manager\Console;

use Illuminate\Console\Command;
use Modules\Manager\Entities\SiteOwner;
use Modules\Manager\Services\EntitlementWriter;

/**
 * Pushes each owner's effective entitlements/theme from the control database
 * into their tenant database(s). Replaces the old HTTP manager:sync.
 */
class PushCommand extends Command
{
    protected $signature = 'manager:push
        {--owner= : Only this site owner id}
        {--host= : Only this host}';

    protected $description = 'Push control-DB entitlements/theme into tenant databases';

    public function handle(EntitlementWriter $writer): int
    {
        $query = SiteOwner::query()->with('domains');

        if ($id = $this->option('owner')) {
            $query->whereKey($id);
        }

        $owners = $query->get();

        if ($owners->isEmpty()) {
            $this->warn('No site owners to push.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($owners as $owner) {
            foreach ($writer->push($owner, $this->option('host') ?: null) as $host) {
                $this->info("pushed: {$owner->name} -> {$host}");
                $total++;
            }
        }

        $this->info("manager:push complete: {$total} host(s).");

        return self::SUCCESS;
    }
}
