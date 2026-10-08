<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `products` into marketplace listings. A listing is sold by one of:
 *   - vendor : a store account (normal order flow, tied to the vendor)
 *   - user   : a registered account without a store
 *   - guest  : no registration; contact details are stored on the listing
 *
 * The promoted/premium fields live on the product (a listing) itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guest listings have no account, so the owner column must be nullable.
        // `store_id` was an unused stub from the earlier multi-vendor attempt.
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->dropColumn('store_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('user_id')
                ->constrained('vendors')->nullOnDelete();
            $table->string('seller_type', 20)->default('user')->after('vendor_id')->index();

            // Guest (and convenience) contact details for the listing.
            $table->string('contact_name')->nullable()->after('seller_type');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_phone');

            // Paid placement.
            $table->boolean('is_promoted')->default(false)->after('contact_email')->index();
            $table->timestamp('promoted_until')->nullable()->after('is_promoted');
            $table->boolean('is_premium')->default(false)->after('promoted_until')->index();
            $table->timestamp('premium_until')->nullable()->after('is_premium');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn([
                'seller_type',
                'contact_name',
                'contact_phone',
                'contact_email',
                'is_promoted',
                'promoted_until',
                'is_premium',
                'premium_until',
            ]);
            $table->foreignId('store_id')->nullable()->after('user_id');
        });
    }
};
