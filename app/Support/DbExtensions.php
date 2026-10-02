<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Detects optional PostgreSQL extensions at runtime. Features that rely on
 * unaccent()/pg_trgm()/vector must degrade gracefully when the database server
 * does not provide them (e.g. the shared global-postgres has vector but not the
 * contrib extensions).
 */
class DbExtensions
{
    /** @var array<string,bool> */
    private static array $cache = [];

    public static function has(string $name): bool
    {
        if (array_key_exists($name, self::$cache)) {
            return self::$cache[$name];
        }

        try {
            if (DB::connection()->getDriverName() !== 'pgsql') {
                return self::$cache[$name] = false;
            }

            return self::$cache[$name] = (bool) DB::connection()
                ->selectOne('select 1 from pg_extension where extname = ?', [$name]);
        } catch (\Throwable) {
            return self::$cache[$name] = false;
        }
    }

    public static function hasUnaccent(): bool
    {
        return self::has('unaccent');
    }

    public static function hasPgTrgm(): bool
    {
        return self::has('pg_trgm');
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
