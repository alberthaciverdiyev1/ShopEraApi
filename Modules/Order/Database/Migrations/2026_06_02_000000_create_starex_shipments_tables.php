<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('starex_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->string('tracking_number')->nullable()->unique();
            $table->string('delivery_type')->nullable();
            $table->string('sync_status')->default('pending')->index();
            $table->string('external_status')->nullable()->index();
            $table->longText('sticker')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();
        });

        Schema::create('starex_shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('starex_shipment_id')->nullable()->constrained('starex_shipments')->nullOnDelete();
            $table->string('event_id')->nullable()->unique();
            $table->string('tracking_number')->index();
            $table->string('status')->index();
            $table->timestamp('event_date')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('starex_shipment_events');
        Schema::dropIfExists('starex_shipments');
    }
};
