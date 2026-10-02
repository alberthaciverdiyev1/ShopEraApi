<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PL/pgSQL trigger; phone normalisation is enforced in the app on other drivers.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_duplicate_active_user_phone()
            RETURNS TRIGGER AS $$
            DECLARE
                normalized_phone TEXT;
            BEGIN
                normalized_phone := REGEXP_REPLACE(COALESCE(NEW.phone, ''), '[^0-9]', '', 'g');

                IF LENGTH(normalized_phone) = 12 AND normalized_phone LIKE '994%' THEN
                    normalized_phone := '0' || RIGHT(normalized_phone, 9);
                ELSIF LENGTH(normalized_phone) = 9 THEN
                    normalized_phone := '0' || normalized_phone;
                END IF;

                IF normalized_phone = '' THEN
                    RETURN NEW;
                END IF;

                NEW.phone := normalized_phone;

                PERFORM pg_advisory_xact_lock(hashtext(normalized_phone));

                IF NEW.deleted_at IS NULL AND EXISTS (
                    SELECT 1
                    FROM users
                    WHERE id <> COALESCE(NEW.id, 0)
                      AND deleted_at IS NULL
                      AND CASE
                            WHEN LENGTH(REGEXP_REPLACE(phone, '[^0-9]', '', 'g')) = 12
                                AND REGEXP_REPLACE(phone, '[^0-9]', '', 'g') LIKE '994%'
                                THEN '0' || RIGHT(REGEXP_REPLACE(phone, '[^0-9]', '', 'g'), 9)
                            WHEN LENGTH(REGEXP_REPLACE(phone, '[^0-9]', '', 'g')) = 9
                                THEN '0' || REGEXP_REPLACE(phone, '[^0-9]', '', 'g')
                            ELSE REGEXP_REPLACE(phone, '[^0-9]', '', 'g')
                          END = normalized_phone
                ) THEN
                    RAISE EXCEPTION 'Phone number already exists' USING ERRCODE = '23505';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS users_prevent_duplicate_active_phone ON users;

            CREATE TRIGGER users_prevent_duplicate_active_phone
            BEFORE INSERT OR UPDATE OF phone, deleted_at ON users
            FOR EACH ROW
            EXECUTE FUNCTION prevent_duplicate_active_user_phone();
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS users_prevent_duplicate_active_phone ON users;
            DROP FUNCTION IF EXISTS prevent_duplicate_active_user_phone();
        SQL);
    }
};
