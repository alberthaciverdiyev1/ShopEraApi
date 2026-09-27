<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('about')->nullable()->after('name');
            $table->text('changes_requested_reason')->nullable()->after('rejection_reason');
            $table->timestamp('changes_requested_at')->nullable()->after('rejected_at');
            $table->timestamp('suspended_at')->nullable()->after('changes_requested_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
        });

        Schema::table('store_order_settlements', function (Blueprint $table) {
            // Released = the hold period is over and the money may be withdrawn.
            $table->timestamp('released_at')->nullable()->after('settled_at');
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('net_amount');
        });

        Schema::table('store_wallet_transactions', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('note');
        });

        Schema::table('store_order_fulfillments', function (Blueprint $table) {
            // Set once, so the pre-deadline reminder is not resent every run.
            $table->timestamp('reminded_at')->nullable()->after('handed_over_at');
        });

        // Sub-order status history, so a merchant can follow their own slice of
        // an order without being shown the whole customer order.
        Schema::create('store_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status', 32)->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'order_id']);
        });

        Schema::create('store_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('store_wallet_transactions')->nullOnDelete();
            $table->timestamps();
            $table->index(['store_id', 'status']);
        });

        Schema::create('marketplace_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('store_min_withdrawal_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('store_release_hold_hours')->default(0);
            $table->boolean('marketplace_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'store_min_withdrawal_amount',
                'store_release_hold_hours',
                'marketplace_enabled',
            ]);
        });

        Schema::dropIfExists('marketplace_audit_logs');
        Schema::dropIfExists('store_withdrawals');
        Schema::dropIfExists('store_order_statuses');

        Schema::table('store_order_fulfillments', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });

        Schema::table('store_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });

        Schema::table('store_order_settlements', function (Blueprint $table) {
            $table->dropColumn(['released_at', 'refunded_amount']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'about',
                'changes_requested_reason',
                'changes_requested_at',
                'suspended_at',
                'suspension_reason',
            ]);
        });
    }
};
