<?php

namespace App\Console\Commands;

use App\Services\Import\DresssoImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDresssoData extends Command
{
    protected $signature = 'dressso:import
        {--source=dressso : Name of the legacy Dressso database on the same server}
        {--dry-run : Run the import and roll it back at the end}';

    protected $description = 'Adapt the legacy Dressso catalogue, accounts and orders into ShopEra';

    public function handle(DresssoImportService $service): int
    {
        if ($this->option('dry-run')) {
            DB::beginTransaction();
        }

        try {
            $result = $service->run((string) $this->option('source'));
        } finally {
            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('Dry run: all changes were rolled back.');
            }
        }

        $this->table(
            ['Table', 'Count'],
            collect($result['stats'])->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        $content = $result['content'];

        if (array_sum($content) === 0) {
            $this->warn('Legacy content tables (banners, FAQ, legal pages) are empty; nothing to import there.');
        } else {
            $this->table(
                ['Content table', 'Rows'],
                collect($content)->map(fn ($count, $key) => [$key, $count])->values()->all()
            );
        }

        return self::SUCCESS;
    }
}
