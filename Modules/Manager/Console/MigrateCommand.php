<?php

namespace Modules\Manager\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs the control-plane migrations against the central `control` connection.
 * Kept separate from `php artisan migrate` so control tables are never created
 * inside a tenant database.
 */
class MigrateCommand extends Command
{
    protected $signature = 'manager:migrate
        {--fresh : Drop and recreate all control tables first}
        {--seed : Seed the control database after migrating}';

    protected $description = 'Migrate the manager control database (connection: control)';

    public function handle(): int
    {
        $path = module_path('Manager', 'Database/ControlMigrations');
        $args = [
            '--database' => 'control',
            '--path' => $path,
            '--realpath' => true,
            '--force' => true,
        ];

        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', $args);
        } else {
            Artisan::call('migrate', $args);
        }

        $this->line(Artisan::output());

        if ($this->option('seed')) {
            Artisan::call('db:seed', [
                '--class' => \Modules\Manager\Database\Seeders\DatabaseSeeder::class,
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }

        return self::SUCCESS;
    }
}
