<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform is no longer sold per subscription: every store is a plain
 * multi-store tenant, so the mirrored subscription and feature-entitlement
 * tables are no longer needed. Promo blocks are unrelated and stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tenant_entitlements');
        Schema::dropIfExists('tenant_subscriptions');
    }

    public function down(): void
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
    }
};
