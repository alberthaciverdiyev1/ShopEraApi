<?php

namespace Modules\Manager\Enums;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::TRIALING => 'Sınaq',
            self::ACTIVE => 'Aktiv',
            self::PAST_DUE => 'Gecikmiş ödəniş',
            self::CANCELLED => 'Ləğv edilib',
            self::EXPIRED => 'Bitib',
        };
    }

    public function isUsable(): bool
    {
        return in_array($this, [self::TRIALING, self::ACTIVE, self::PAST_DUE], true);
    }
}
