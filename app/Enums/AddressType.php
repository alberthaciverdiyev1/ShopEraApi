<?php

namespace App\Enums;

enum AddressType :int
{
    case STANDARD = 0;
    case STANDARD_FAST = 1;
    case PICKUP_POINT = 2;
    case TAKE_FROM_STORE = 3;

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => __('Standard'),
            self::STANDARD_FAST => __('Standard Fast'),
            self::PICKUP_POINT => __('Pickup Point'),
            self::TAKE_FROM_STORE => __('Take From Store'),
        };
    }
}
