<?php

namespace App\Console\Commands;

use App\Helpers\PhoneHelper;
use App\Support\TenantDatabase;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Entities\User;

/**
 * Creates a tenant database and runs migrations (+ optional seed) against it.
 * One codebase, a separate database per site owner.
 */
class TenantProvision extends Command
{
    protected $signature = 'tenant:provision {host} {--seed : Run full demo seeders}
        {--fresh : Drop and recreate}
        {--database= : Explicit tenant database name from Manager}
        {--admin-email= : Create a store admin with this e-mail}
        {--admin-password= : Password for the store admin}
        {--admin-name= : Name for the store admin}
        {--admin-phone= : Phone for the store admin}';

    protected $description = 'Provision a tenant database for a host';

    public function handle(): int
    {
        $host = (string) $this->argument('host');
        $database = (string) ($this->option('database') ?: $this->databaseFor($host));
        $database = $this->sanitizeDatabase($database);

        $admin = DB::connection(TenantDatabase::centralConnection());
        $previous = config('database.default');
        $safe = str_replace('"', '', $database);

        try {
            if ($this->option('fresh')) {
                $admin->statement('DROP DATABASE IF EXISTS "'.$safe.'"');
            }

            $exists = $admin->table('pg_database')->where('datname', $database)->exists();

            if (! $exists) {
                $admin->statement('CREATE DATABASE "'.$safe.'"');
                $this->info("Created database {$database}.");
            } else {
                $this->warn("Database {$database} already exists.");
            }

            config(['database.connections.tenant.database' => $database]);
            DB::purge('tenant');
            DB::setDefaultConnection('tenant');

            $this->call('migrate', ['--database' => 'tenant', '--force' => true]);

            // Always seed the essentials (roles, permissions, settings, theme…).
            $this->call('db:seed', [
                '--class' => TenantDatabaseSeeder::class,
                '--database' => 'tenant',
                '--force' => true,
            ]);

            // Optional full demo dataset.
            if ($this->option('seed')) {
                $this->call('db:seed', ['--database' => 'tenant', '--force' => true]);
            }

            if ($email = $this->option('admin-email')) {
                $this->createAdmin($email, (string) $this->option('admin-password'), (string) $this->option('admin-name'), (string) $this->option('admin-phone'));
            }
        } finally {
            // A freshly created database must be resolvable immediately, even
            // when the host is not yet present in the Manager map cache.
            TenantDatabase::forgetExistence($database);

            // Never leak the tenant connection into the rest of the process
            // (the webhook runs tenant:provision inside a live HTTP request).
            DB::setDefaultConnection($previous);
            DB::purge('tenant');
        }

        $this->info("Tenant {$host} → {$database} ready.");

        return self::SUCCESS;
    }

    private function createAdmin(string $email, string $password, string $name, string $phone = ''): void
    {
        $phone = PhoneHelper::normalize($phone);

        if ($phone === '') {
            $phone = '0500'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('admin');

        $this->info("Admin created: {$email}");
    }

    private function databaseFor(string $host): string
    {
        $map = (array) TenantDatabase::cache()->get(config('tenant.map_cache'), []);

        if (! empty($map[$host])) {
            return $map[$host];
        }

        return TenantDatabase::nameFor($host);
    }

    private function sanitizeDatabase(string $database): string
    {
        $database = trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $database), '_');

        if ($database === '') {
            throw new \InvalidArgumentException('Tenant database name cannot be empty.');
        }

        return $database;
    }
}
