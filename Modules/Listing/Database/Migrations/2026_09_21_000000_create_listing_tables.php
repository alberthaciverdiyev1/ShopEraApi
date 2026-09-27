<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The classifieds section: car and property ads to begin with, and whatever
 * the admin adds later.
 *
 * The point of the shape below is that a section is data, not code. A section
 * owns its fields (listing_attributes) and their choices
 * (listing_attribute_options), and an ad's answers live in listing_values, so
 * adding "jobs" or "electronics" is a few minutes in the admin panel instead
 * of a release.
 *
 * Every table here is new and nothing the shop already uses is touched, so the
 * apps in the stores cannot notice this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('name');
            // The screens are not generated from nothing: a section picks one
            // of the shipped layouts - plain, vehicle-style (a table of
            // fields), property-style (with the map).
            $table->string('template', 20)->default('simple');
            $table->string('icon_path')->nullable();
            $table->string('banner_path')->nullable();
            // Shown next to the call button, e.g. "beh göndərmək tövsiyə
            // olunmur". Per section, because the advice differs by what is
            // being sold.
            $table->json('warning_text')->nullable();
            // Per section as well: property can go live at once while cars
            // wait for a moderator.
            $table->boolean('auto_approve')->default(false);
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('listing_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('listing_sections')->cascadeOnDelete();
            // Model depends on make: the child's options are filtered by the
            // option chosen in the parent.
            $table->foreignId('parent_id')->nullable()->constrained('listing_attributes')->cascadeOnDelete();
            $table->string('key', 40);
            $table->json('label');
            // text, number, select, multiselect, boolean, date.
            $table->string('type', 20);
            $table->string('unit', 16)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('in_filter')->default(false)->index();
            // Shown on the ad card in the list, as mileage and year are.
            $table->boolean('in_card')->default(false);
            // A number filter asked as "from - to" rather than one value.
            $table->boolean('is_range')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section_id', 'key']);
        });

        Schema::create('listing_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('listing_attributes')->cascadeOnDelete();
            // The make this model belongs to.
            $table->foreignId('parent_option_id')->nullable()->constrained('listing_attribute_options')->cascadeOnDelete();
            $table->string('value', 80);
            $table->json('label');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'value']);
            $table->index('parent_option_id');
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('listing_sections')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable()->index();
            $table->string('currency', 3)->default('AZN');
            $table->boolean('is_negotiable')->default(false);

            // The seller drops a pin or searches an address; the device's GPS
            // is never asked for, so the app needs no location permission.
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 32)->nullable();

            // draft, pending, active, rejected, expired, archived.
            $table->string('status', 20)->default('pending');
            $table->text('moderation_note')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            $table->boolean('is_vip')->default(false)->index();
            $table->timestamp('vip_until')->nullable();

            $table->timestamp('published_at')->nullable();
            // An ad that runs out is not deleted: it drops into the owner's
            // archive and one tap puts it back.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('renewed_at')->nullable();

            $table->unsignedInteger('views')->default(0);
            // A copy of the answers for rendering a card or a page without
            // joining listing_values. Filtering still goes through the values.
            $table->jsonb('attributes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['section_id', 'status', 'published_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('listing_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('listing_attributes')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('listing_attribute_options')->cascadeOnDelete();
            // One row holds one answer; the column used depends on the
            // attribute's type. Typed columns keep "year from 2015 to 2020"
            // and "make = BMW" as plain indexed lookups.
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 4)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->date('value_date')->nullable();
            $table->timestamps();

            $table->index(['listing_id']);
            $table->index(['attribute_id', 'option_id']);
            $table->index(['attribute_id', 'value_number']);
        });

        Schema::create('listing_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            // image or video.
            $table->string('type', 10);
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            // A video is squeezed in the background, so it is not ready the
            // moment it is uploaded.
            $table->string('status', 16)->default('ready');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_media');
        Schema::dropIfExists('listing_values');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('listing_attribute_options');
        Schema::dropIfExists('listing_attributes');
        Schema::dropIfExists('listing_sections');
    }
};
