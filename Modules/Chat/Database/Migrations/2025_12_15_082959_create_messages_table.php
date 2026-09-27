<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();

            $table->enum('sender_type', ['admin', 'user']);
            $table->unsignedBigInteger('sender_id');

            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);

            $table->timestamps();

            $table->index(['sender_type', 'sender_id']);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
