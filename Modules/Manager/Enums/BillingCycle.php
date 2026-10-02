<?php

namespace Modules\Manager\Enums;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Aylıq',
            self::YEARLY => 'İllik',
        };
    }
}
