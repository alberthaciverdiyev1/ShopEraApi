<?php

use Modules\Manager\Entities\SiteOwner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            $table->string('tenant_slug')->nullable()->unique()->after('db_name');
        });

        $used = [];

        SiteOwner::query()
            ->with('domains')
            ->orderBy('id')
            ->get()
            ->each(function (SiteOwner $owner) use (&$used) {
                $slug = $this->uniqueSlug($owner->tenantSlug(), $used);
                $used[] = $slug;
                $owner->forceFill([
                    'tenant_slug' => $slug,
                    'db_name' => $owner->db_name ?: 'shopera_'.str_replace('-', '_', substr($slug, 0, 48)),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            $table->dropColumn('tenant_slug');
        });
    }

    private function uniqueSlug(string $slug, array $used): string
    {
        $base = $slug !== '' ? $slug : 'tenant';
        $candidate = $base;
        $counter = 2;

        while (in_array($candidate, $used, true)) {
            $suffix = '-'.$counter++;
            $candidate = substr($base, 0, 50 - strlen($suffix)).$suffix;
        }

        return $candidate;
    }
};
