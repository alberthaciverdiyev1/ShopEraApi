<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local snapshot of this instance's subscription, feature entitlements and
 * promo blocks, mirrored from Manager.Snaker (source of truth) by manager:sync
 * and the manager webhook. Kept in the tenant's own database so every site
 * reads its own plan even when one codebase serves many hosts (free plan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_owner_id')->nullable()->index();
            $table->string('host')->nullable()->index();
            $table->string('plan_name')->nullable();
            $table->string('status')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('usable')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_entitlements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->string('type')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_promo_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('offer')->index();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('button_text')->nullable();
            $table->string('url')->nullable();
            $table->string('badge')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_promo_blocks');
        Schema::dropIfExists('tenant_entitlements');
        Schema::dropIfExists('tenant_subscriptions');
    }
};
