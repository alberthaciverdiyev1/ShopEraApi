<?php

namespace App\Console\Commands;

use App\Support\TenantDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DailyDatabaseBackup extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Backup the central and every tenant database locally';

    public function handle(): int
    {
        $path = storage_path('app/backups/');

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        // The central catalog plus every tenant database on the same server.
        $databases = [(string) config('database.connections.pgsql.database')];
        foreach (array_keys(TenantDatabase::databases()) as $tenantDatabase) {
            if (! in_array($tenantDatabase, $databases, true)) {
                $databases[] = $tenantDatabase;
            }
        }

        $failed = 0;

        foreach (array_filter($databases) as $database) {
            if (! $this->backup($database, $path)) {
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error("{$failed} database backup(s) failed. Check laravel.log for details.");

            return self::FAILURE;
        }

        $this->cleanupOldBackups();

        $this->info('Backup completed for '.count($databases).' database(s).');

        return self::SUCCESS;
    }

    protected function backup(string $database, string $path): bool
    {
        $filename = 'backup-'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $database).'-'.now()->format('Y-m-d_H-i-s').'.sql';

        $command = sprintf(
            'export PGPASSWORD="%s" && pg_dump -h %s -p %s -U %s %s > %s',
            config('database.connections.pgsql.password'),
            config('database.connections.pgsql.host'),
            config('database.connections.pgsql.port'),
            config('database.connections.pgsql.username'),
            $database,
            $path.$filename
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Database backup failed', [
                'database' => $database,
                'filename' => $filename,
                'error' => $process->getErrorOutput(),
            ]);
            $this->error("Backup failed for {$database}!");

            return false;
        }

        Log::info('Database backup successful', ['database' => $database, 'filename' => $filename]);
        $this->info("Backed up {$database} → {$filename}");

        return true;
    }

    protected function cleanupOldBackups(): void
    {
        $files = glob(storage_path('app/backups/*.sql'));
        $threshold = now()->subDays(7)->getTimestamp();

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                if (unlink($file)) {
                    Log::info('Old backup file deleted', ['file' => basename($file)]);
                } else {
                    Log::warning('Could not delete old backup file', ['file' => basename($file)]);
                }
            }
        }
    }
}
