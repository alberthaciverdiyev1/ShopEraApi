<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer <-> seller chat scoped to a listing. Kept separate from the support
 * Chat module (which is user <-> admin) so neither interferes with the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'buyer_id']);
            $table->index('seller_id');
        });

        Schema::create('listing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('listing_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_messages');
        Schema::dropIfExists('listing_conversations');
    }
};
