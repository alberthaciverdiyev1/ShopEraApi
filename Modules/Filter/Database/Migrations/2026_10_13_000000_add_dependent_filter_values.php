<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the flat filter system into a dependent value tree.
 *
 * A filter belongs to a subcategory and may depend on another filter
 * (Model depends on Brand, Storage depends on Model, …). Its values form a
 * tree through `parent_value_id`, so a child's options are the values whose
 * parent is the currently selected parent value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filters', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('depends_on_filter_id')->nullable()->after('type')->constrained('filters')->nullOnDelete();
            $table->integer('sort_order')->default(0)->after('options');
            $table->boolean('required')->default(false)->after('sort_order');
        });

        Schema::create('filter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filter_id')->constrained('filters')->cascadeOnDelete();
            $table->foreignId('parent_value_id')->nullable()->constrained('filter_values')->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['filter_id', 'parent_value_id']);
        });

        Schema::create('product_filter_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('filter_id')->constrained('filters')->cascadeOnDelete();
            $table->foreignId('filter_value_id')->constrained('filter_values')->cascadeOnDelete();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_filter_values');
        Schema::dropIfExists('filter_values');

        Schema::table('filters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depends_on_filter_id');
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['sort_order', 'required']);
        });
    }
};
