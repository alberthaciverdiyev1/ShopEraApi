<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('owner_full_name');
            $table->string('name')->index();
            $table->string('identity_front_path');
            $table->string('identity_back_path');
            $table->string('logo_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_trusted')->default(false)->index();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('commission_percent_override', 5, 2)->nullable();
            $table->decimal('negative_balance_limit_override', 12, 2)->nullable();
            $table->string('deactivated_reason', 64)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('instructions_accepted_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('store_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('store_order_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_type', 20);
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('store_wallet_transactions')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'order_id']);
        });

        Schema::create('store_order_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status', 20)->default('awaiting')->index();
            $table->timestamp('handover_due_at');
            $table->timestamp('handed_over_at')->nullable();
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->timestamp('penalized_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'order_id']);
            $table->index(['status', 'handover_due_at']);
        });

        Schema::create('store_wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('waiting')->index();
            $table->string('provider_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_wallet_topups');
        Schema::dropIfExists('store_order_fulfillments');
        Schema::dropIfExists('store_order_settlements');
        Schema::dropIfExists('store_wallet_transactions');
        Schema::dropIfExists('stores');
    }
};
