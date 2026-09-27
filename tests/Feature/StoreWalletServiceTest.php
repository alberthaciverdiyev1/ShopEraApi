<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Services\StoreWalletService;
use Tests\TestCase;

class StoreWalletServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('owner_full_name');
            $table->string('name');
            $table->string('identity_front_path');
            $table->string('identity_back_path');
            $table->string('status')->default('pending');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_trusted')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('commission_percent_override', 5, 2)->nullable();
            $table->decimal('negative_balance_limit_override', 12, 2)->nullable();
            $table->string('deactivated_reason')->nullable();
            $table->timestamp('instructions_accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('store_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('store_wallet_transactions');
        Schema::dropIfExists('stores');
        parent::tearDown();
    }

    public function test_store_deactivates_at_limit_and_reactivates_after_top_up(): void
    {
        $store = $this->store();
        $service = app(StoreWalletService::class);

        $service->add($store, -10, 'cash_sale_commission', enforceNegativeLimit: true);
        $store->refresh();
        $this->assertSame(-10.0, (float) $store->balance);
        $this->assertFalse($store->is_active);
        $this->assertSame('negative_balance_limit', $store->deactivated_reason);

        $service->add($store, 1, 'top_up');
        $store->refresh();
        $this->assertTrue($store->is_active);
        $this->assertNull($store->deactivated_reason);
    }

    public function test_limit_is_enforced_and_idempotency_does_not_double_credit(): void
    {
        $store = $this->store();
        $service = app(StoreWalletService::class);

        try {
            $service->add($store, -10.01, 'cash_sale_commission', enforceNegativeLimit: true);
            $this->fail('Expected negative limit validation exception.');
        } catch (ValidationException) {
            $this->assertSame(0.0, (float) $store->fresh()->balance);
        }

        $first = $service->add($store, 5, 'top_up', idempotencyKey: 'same-top-up');
        $second = $service->add($store, 5, 'top_up', idempotencyKey: 'same-top-up');
        $this->assertSame($first->id, $second->id);
        $this->assertSame(5.0, (float) $store->fresh()->balance);
    }

    private function store(): Store
    {
        return Store::create([
            'user_id' => 1,
            'owner_full_name' => 'Test Owner',
            'name' => 'Test Store',
            'identity_front_path' => 'front.jpg',
            'identity_back_path' => 'back.jpg',
            'status' => 'approved',
            'is_active' => true,
            'balance' => 0,
            'commission_percent_override' => 10,
            'negative_balance_limit_override' => 10,
            'instructions_accepted_at' => now(),
        ]);
    }
}
