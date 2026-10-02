<?php

namespace Modules\Order\Database\Seeders;

use App\Enums\AddressType;
use App\Enums\BalanceType;
use App\Enums\OrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Balance\Entities\Balance;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderItem;
use Modules\Order\Entities\OrderStatus as OrderStatusModel;
use Modules\Product\Entities\Product;
use Modules\PromoCode\Entities\PromoCode;
use Modules\User\Entities\User;

class OrderDatabaseSeeder extends Seeder
{
    /**
     * Purchase history built from the real customer base, addresses, products
     * and delivery tariffs. Every order carries its items, a status timeline,
     * an optional promo usage and the matching balance movement.
     */
    public function run(): void
    {
        $customers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'user'))
            ->with('addresses')
            ->get()
            ->filter(fn (User $u) => $u->addresses->isNotEmpty())
            ->values();

        $products = Product::query()->with(['colors', 'sizes'])->get();
        $promoCodes = PromoCode::query()->where('is_active', true)->get();
        $delivery = DB::table('delivery_prices')->get()->keyBy('city_name');

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Seed-generated history is rebuilt from scratch so re-running stays
        // idempotent and never leaves dangling references behind.
        DB::table('used_promo_codes')->delete();
        DB::table('order_statuses')->delete();
        DB::table('order_items')->delete();
        DB::table('baskets')->update(['is_ordered' => false, 'transaction_id' => null]);
        Order::query()->delete();
        Balance::query()->forceDelete();

        mt_srand(20260930);

        $orderSeq = 1000;
        $usedPromo = [];

        foreach ($customers as $customer) {
            $address = $customer->addresses->first();
            $ordersCount = mt_rand(1, 4);

            for ($o = 0; $o < $ordersCount; $o++) {
                $orderDate = now()->subDays(mt_rand(2, 150))->setTime(mt_rand(9, 21), mt_rand(0, 59));
                $transactionId = 'SHP-'.date('Y', $orderDate->timestamp).'-'.str_pad((string) (++$orderSeq), 5, '0', STR_PAD_LEFT);

                $itemCount = mt_rand(1, 3);
                $picked = $products->random($itemCount);

                $subtotal = 0.0;
                $items = [];

                foreach ($picked as $product) {
                    $quantity = mt_rand(1, 2);
                    $unit = (float) (($product->discount !== null && (float) $product->discount > 0) ? $product->discount : $product->price);
                    $line = round($unit * $quantity, 2);
                    $subtotal += $line;

                    $items[] = [
                        'product_id' => $product->id,
                        'color_id' => $product->colors->isNotEmpty() ? $product->colors->random()->id : null,
                        'size_id' => $product->sizes->isNotEmpty() ? $product->sizes->random()->id : null,
                        'quantity' => $quantity,
                        'unit_price' => $unit,
                        'total_price' => $line,
                    ];
                }

                $subtotal = round($subtotal, 2);

                // Promo (only some orders).
                $promo = null;
                if (mt_rand(1, 100) <= 35) {
                    $promo = $promoCodes->random();
                }
                $discountPrice = $promo ? round($subtotal * (float) $promo->discount_percent / 100, 2) : 0.0;

                // Shipping from the customer's city tariff.
                $tariff = $delivery[$address->city] ?? null;
                $shippingPrice = 0.0;
                $addressType = AddressType::STANDARD->value;
                if ($tariff) {
                    $shippingPrice = ((float) $tariff->free_from > 0 && $subtotal >= (float) $tariff->free_from)
                        ? 0.0
                        : (float) $tariff->price;
                }

                $total = round($subtotal - $discountPrice + $shippingPrice, 2);

                // Status progression depends on how old the order is.
                $status = $this->resolveStatus($orderDate);

                $paymentType = ['card', 'cash', 'e_point'][mt_rand(0, 2)];
                $paidAt = in_array($status, [OrderStatus::DELIVERED, OrderStatus::PROCESSING, OrderStatus::PLACED], true)
                    ? $orderDate->copy()->addMinutes(mt_rand(2, 40))
                    : null;

                $order = Order::create([
                    'user_id' => $customer->id,
                    'address_id' => $address->id,
                    'transaction_id' => $transactionId,
                    'total_price' => $total,
                    'discount_price' => $discountPrice,
                    'shipping_price' => $shippingPrice,
                    'paid_at' => $paidAt,
                    'note' => mt_rand(0, 1) ? 'Zəhmət olmasa qapıya çatdırın.' : null,
                    'promo_code' => $promo?->code,
                    'address_type' => $addressType,
                    'address_type_id' => $address->id,
                    'payment_type' => $paymentType,
                    'pricing_type' => 'retail',
                    'return_to_balance' => $status === OrderStatus::RETURNED,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                foreach ($items as $item) {
                    OrderItem::create($item + [
                        'order_id' => $order->id,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                }

                // Status timeline.
                $this->seedStatuses($order, $status, $orderDate);

                // Promo usage record.
                if ($promo) {
                    DB::table('used_promo_codes')->insert([
                        'promo_code_id' => $promo->id,
                        'user_id' => $customer->id,
                        'order_id' => $order->id,
                        'transaction_id' => $transactionId,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                    $usedPromo[$promo->id] = ($usedPromo[$promo->id] ?? 0) + 1;
                }

                // Balance movement tied to the order.
                $this->seedBalance($customer, $order, $status, $orderDate);

                // Link an existing open basket item to the order when it matches.
                DB::table('baskets')
                    ->where('user_id', $customer->id)
                    ->whereIn('product_id', array_column($items, 'product_id'))
                    ->update(['is_ordered' => true, 'transaction_id' => $transactionId]);
            }
        }

        // Reflect promo usage counts.
        foreach ($usedPromo as $promoId => $count) {
            PromoCode::where('id', $promoId)->update(['user_count' => max(0, (int) PromoCode::where('id', $promoId)->value('user_count') - $count)]);
        }

        // Ensure every customer has a starting wallet top-up.
        foreach ($customers as $customer) {
            if (! Balance::where('user_id', $customer->id)->where('type', BalanceType::DEPOSIT->value)->exists()) {
                Balance::create([
                    'user_id' => $customer->id,
                    'type' => BalanceType::DEPOSIT->value,
                    'amount' => mt_rand(50, 300),
                    'note' => 'Balans artırılması',
                    'created_at' => now()->subDays(mt_rand(30, 180)),
                    'updated_at' => now()->subDays(mt_rand(1, 30)),
                ]);
            }
        }
    }

    private function resolveStatus(Carbon $orderDate): OrderStatus
    {
        $days = $orderDate->diffInDays(now());

        return match (true) {
            $days > 30 => [OrderStatus::DELIVERED, OrderStatus::DELIVERED, OrderStatus::DELIVERED, OrderStatus::RETURNED][mt_rand(0, 3)],
            $days > 10 => [OrderStatus::DELIVERED, OrderStatus::PROCESSING][mt_rand(0, 1)],
            $days > 3 => OrderStatus::PROCESSING,
            default => [OrderStatus::PLACED, OrderStatus::WAITING_PAYMENT][mt_rand(0, 1)],
        };
    }

    private function seedStatuses(Order $order, OrderStatus $final, Carbon $orderDate): void
    {
        $journey = match ($final) {
            OrderStatus::RETURNED => [OrderStatus::PLACED, OrderStatus::PROCESSING, OrderStatus::DELIVERED, OrderStatus::RETURNED],
            OrderStatus::DELIVERED => [OrderStatus::PLACED, OrderStatus::PROCESSING, OrderStatus::DELIVERED],
            OrderStatus::PROCESSING => [OrderStatus::PLACED, OrderStatus::PROCESSING],
            OrderStatus::WAITING_PAYMENT => [OrderStatus::WAITING_PAYMENT],
            default => [OrderStatus::PLACED],
        };

        $at = $orderDate->copy();
        foreach ($journey as $status) {
            OrderStatusModel::create([
                'order_id' => $order->id,
                'status' => $status->value,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $at = $at->copy()->addHours(mt_rand(6, 36));
        }
    }

    private function seedBalance(User $customer, Order $order, OrderStatus $status, Carbon $orderDate): void
    {
        if ($status === OrderStatus::RETURNED && $order->return_to_balance) {
            Balance::create([
                'user_id' => $customer->id,
                'type' => BalanceType::REFUND->value,
                'amount' => $order->total_price,
                'transaction_order' => $order->transaction_id,
                'note' => 'Sifarişin geri qaytarılması: '.$order->transaction_id,
                'created_at' => $orderDate->copy()->addDays(2),
                'updated_at' => $orderDate->copy()->addDays(2),
            ]);
        }
    }
}
