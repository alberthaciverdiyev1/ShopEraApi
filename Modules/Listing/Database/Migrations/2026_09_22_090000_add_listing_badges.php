<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Badges on an ad, and the price drop that feeds one of them.
 *
 * A badge says what the ad is — "kredit var", "vuruğu yoxdur", "çıxarış var" —
 * and the app draws it from a fixed registry of keys. Which answer earns which
 * badge stays data: a boolean field carries the key it stands for, and a
 * choice (say "Təmirli") can carry one too. So a new section marks its own
 * fields in the panel and the app needs no release.
 *
 * Nothing here is read by the shipped apps; every column is nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_sections', function (Blueprint $table) {
            // How an ad in this section names itself: "{make} {model}" for
            // cars, "{property_type}, {city}" for flats. The ad form fills the
            // braces from the answers, so the seller is never asked to write a
            // title - which is how Turbo and Bina work.
            $table->string('title_template')->nullable()->after('template');
        });

        Schema::table('listing_attributes', function (Blueprint $table) {
            // One of the keys the app knows: vip, credit, mortgage, barter,
            // no_damage, extract, repair, complex.
            $table->string('badge_key', 24)->nullable()->after('is_active');
            // Which answer earns it. "Vuruğu var" and "Rənglənib" both carry
            // no_damage with badge_when=false, and the badge only shows when
            // every field behind it agrees - that is the "and" of the two.
            $table->string('badge_when', 8)->nullable()->after('badge_key');
        });

        Schema::table('listing_attribute_options', function (Blueprint $table) {
            $table->string('badge_key', 24)->nullable()->after('is_active');
            // Lets the ad form draw real swatches instead of a list of colour
            // names. Empty for every field that is not a colour.
            $table->string('color_hex', 7)->nullable()->after('badge_key');
        });

        Schema::table('listings', function (Blueprint $table) {
            // What the ad cost before the seller lowered it, so the list can
            // show the drop for a week without keeping a price history.
            $table->decimal('previous_price', 12, 2)->nullable()->after('price');
            $table->timestamp('price_dropped_at')->nullable()->after('previous_price');
            // The keys the ad's own answers earned, worked out when the ad is
            // written so a page of cards needs no extra query. vip and the
            // price drop are time-based and stay live.
            $table->jsonb('badges')->nullable()->after('attribute_values');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['previous_price', 'price_dropped_at', 'badges']);
        });

        Schema::table('listing_attribute_options', function (Blueprint $table) {
            $table->dropColumn(['badge_key', 'color_hex']);
        });

        Schema::table('listing_sections', function (Blueprint $table) {
            // How an ad in this section names itself: "{make} {model}" for
            // cars, "{property_type}, {city}" for flats. The ad form fills the
            // braces from the answers, so the seller is never asked to write a
            // title - which is how Turbo and Bina work.
            $table->string('title_template')->nullable()->after('template');
        });

        Schema::table('listing_attributes', function (Blueprint $table) {
            $table->dropColumn(['badge_key', 'badge_when']);
        });

        Schema::table('listing_sections', function (Blueprint $table) {
            $table->dropColumn('title_template');
        });
    }
};
