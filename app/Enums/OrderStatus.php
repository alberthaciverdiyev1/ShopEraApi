<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PLACED = 0;
    case PROCESSING = 1;
    case DELIVERED = 2;
    case RETURNED= 3;
    case WAITING_PAYMENT = 4;
    case FAILED = 5;
    case CANCELLED = 6;
    case ADMIN_WAITING = 7;

    public function label(): string
    {
        return match ($this) {
            self::WAITING_PAYMENT => __('Waiting Payment'),
            self::PLACED => __('Order Placed'), //cancel ola bilsin
            self::PROCESSING => __('Processing'), //cancel ola bilsin
            self::DELIVERED => __('Delivered'),
            self::RETURNED => __('Returned'),
            self::FAILED => __('Failed'),
            self::CANCELLED => __('Cancelled'),
            self::ADMIN_WAITING => __('Admin Waiting'),
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::PLACED,
            1 => self::PROCESSING,
            2 => self::DELIVERED,
            3 => self::RETURNED,
            4 => self::WAITING_PAYMENT,
            5 => self::FAILED,
            6 => self::CANCELLED,
            7 => self::ADMIN_WAITING,
            default => throw new \InvalidArgumentException("Invalid OrderStatus value: {$value}"),
        };
    }

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'order placed', 'placed' => self::PLACED,
            'processing' => self::PROCESSING,
            'delivered' => self::DELIVERED,
            'returned' => self::RETURNED,
            'waiting payment' => self::WAITING_PAYMENT,
            'failed' => self::FAILED,
            'cancelled' => self::CANCELLED,
            'admin waiting' => self::ADMIN_WAITING,
            default => throw new \InvalidArgumentException("Invalid OrderStatus string: {$value}"),
        };
    }
    public static function labelFromValue(int $value): string
    {
        try {
            return self::from($value)->label();
        } catch (\Throwable) {
            return (string) $value;
        }
    }
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::PLACED,
            self::PROCESSING,
            self::WAITING_PAYMENT,
//            self::ADMIN_WAITING,
        ], true);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this, [
            self::PLACED,
            self::WAITING_PAYMENT,
        ], true);
    }

    public function isPaid(): bool
    {
        return in_array($this, [
            self::PLACED,
            self::PROCESSING,
            self::DELIVERED,
        ], true);
    }

    public static function resolve(int|string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_int($value) || ctype_digit((string) $value)) {
            return self::from((int) $value);
        }

        return self::fromString((string) $value);
    }
    public function getNotificationMessage(int $orderId): array
    {
        return match($this) {
            self::PLACED => [
                'title' => __('Order Received'),
                'body' => __('Your order #:id has been successfully received.', ['id' => $orderId])
            ],
            self::PROCESSING => [
                'title' => __('Order Processing'),
                'body' => __('Your order #:id is currently being processed.', ['id' => $orderId])
            ],
            self::DELIVERED => [
                'title' => __('Order Delivered'),
                'body' => __('Your order #:id has been delivered. Would you like to rate the products?', ['id' => $orderId])
            ],
            self::RETURNED => [
                'title' => __('Order Returned'),
                'body' => __('Your order #:id has been marked as returned.', ['id' => $orderId])
            ],
            self::WAITING_PAYMENT => [
                'title' => __('Awaiting Payment'),
                'body' => __('Payment for your order #:id has not been completed yet.', ['id' => $orderId])
            ],
            self::FAILED => [
                'title' => __('Order Failed'),
                'body' => __('An error occurred with your order #:id.', ['id' => $orderId])
            ],
            self::CANCELLED => [
                'title' => __('Order Cancelled'),
                'body' => __('Your order #:id has been cancelled.', ['id' => $orderId])
            ],
            self::ADMIN_WAITING => [
                'title' => __('Order Cancellation Request Received'),
                'body' => __('Your cancellation request for order #:id has been received.', ['id' => $orderId])
            ],
        };
    }
}
