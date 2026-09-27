<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DailyDatabaseBackup extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup the database and store it locally';

    public function handle()
    {
        $filename = "backup-" . now()->format('Y-m-d_H-i-s') . ".sql";
        $path = storage_path("app/backups/");

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $command = sprintf(
            'export PGPASSWORD="%s" && pg_dump -h %s -p %s -U %s %s > %s',
            config('database.connections.pgsql.password'),
            config('database.connections.pgsql.host'),
            config('database.connections.pgsql.port'),
            config('database.connections.pgsql.username'),
            config('database.connections.pgsql.database'),
            $path . $filename
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();

            Log::error('Database backup failed', [
                'filename' => $filename,
                'error'    => $errorOutput
            ]);

            $this->error("Backup failed! Check laravel.log for details.");
            return 1;
        }

        Log::info('Database backup successful', ['filename' => $filename]);
        $this->info("Backup completed successfully: {$filename}");

        $this->cleanupOldBackups();
        return 0;
    }

    protected function cleanupOldBackups()
    {
        $files = glob(storage_path("app/backups/*.sql"));
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
