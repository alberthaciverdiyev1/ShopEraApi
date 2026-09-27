<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers that a product was once approved, even while it is waiting again.
 *
 * When a seller edits an approved product it goes back in the queue, and the
 * edit nulls `approved_at`, `approved_by` and `rejection_reason` — every field
 * that could have proved the product had ever been live. The admin then sees a
 * row indistinguishable from a brand-new submission, with an old creation date
 * on it, and reads it as a duplicate. That is how the same products have been
 * rejected two and three times.
 *
 * `approved_at` cannot simply be left alone: it is already published to all
 * three clients and means "currently approved at". A second column keeps that
 * meaning intact and answers the different question — "was this ever live?".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('last_approved_at')->nullable()->after('approved_at');
        });

        // Everything approved right now was, self-evidently, approved.
        DB::table('products')
            ->whereNotNull('approved_at')
            ->update(['last_approved_at' => DB::raw('approved_at')]);

        // Anything already waiting or rejected has lost its proof — the edit
        // that queued it nulled the timestamp. The audit log is the only place
        // the history survives, so recover what it holds.
        if (Schema::hasTable('marketplace_audit_logs')) {
            DB::statement(<<<'SQL'
                UPDATE products p
                SET last_approved_at = a.approved_at
                FROM (
                    SELECT subject_id, MAX(created_at) AS approved_at
                    FROM marketplace_audit_logs
                    WHERE action = 'product.approval_changed'
                      AND subject_type LIKE '%Product'
                      AND changes::text LIKE '%"approved"%'
                    GROUP BY subject_id
                ) a
                WHERE p.id = a.subject_id
                  AND p.last_approved_at IS NULL
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('last_approved_at');
        });
    }
};
