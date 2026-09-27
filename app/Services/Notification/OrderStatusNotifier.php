<?php

namespace App\Services\Notification;

use App\Enums\OrderStatus as OrderStatusEnum;
use App\Jobs\SendOrderStatusNotificationJob;
use Illuminate\Support\Facades\Log;
use Modules\Order\Http\Entities\Order;

/**
 * Turns an order status change into a customer notification.
 *
 * Every path that writes an order_statuses row goes through here, so the
 * customer hears about their order whether the status came from the admin
 * panel, the payment callback, the courier webhook or the sweeper that expires
 * unpaid orders. Before this existed only the admin panel notified.
 */
class OrderStatusNotifier
{
    public function notify(Order $order, OrderStatusEnum $status): void
    {
        try {
            $message = $status->getNotificationMessage($order->id);

            if (empty($message['title'])) {
                return;
            }

            $isDelivered = $status === OrderStatusEnum::DELIVERED;

            $extraData = [
                'order_id' => (string) $order->id,
                'type' => $isDelivered ? 'WRITE_REVIEW' : 'ORDER_DETAILS',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ];

            if ($isDelivered) {
                $extraData['product_id'] = (string) $this->firstProductId($order);
            }

            // afterCommit so a rolled-back order never announces itself.
            SendOrderStatusNotificationJob::dispatch(
                $order->user_id,
                $order->id,
                $status->name,
                $message['title'],
                $message['body'],
                $extraData
            )->afterCommit();
        } catch (\Throwable $e) {
            // A notification must never break the order flow it reports on.
            Log::error('Order status notification could not be queued.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $status->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Same thing when the caller only has an id to hand.
     */
    public function notifyById(int $orderId, OrderStatusEnum $status): void
    {
        $order = Order::find($orderId);

        if ($order) {
            $this->notify($order, $status);
        }
    }

    private function firstProductId(Order $order): ?int
    {
        return $order->relationLoaded('items')
            ? $order->items->first()?->product_id
            : $order->items()->value('product_id');
    }
}
