<?php

namespace App\Enums;

enum Gender: int
{
    case MALE = 0;
    case FEMALE = 1;
    case KIDS = 2;
    case UNISEX = 3;

    public function label(): string
    {
        return match ($this) {
            self::MALE => __('Male'),
            self::FEMALE => __('Female'),
            self::KIDS => __('Kids'),
            self::UNISEX => __('Unisex'),
        };
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::MALE,
            1 => self::FEMALE,
            2 => self::KIDS,
            3 => self::UNISEX,
            default => throw new \InvalidArgumentException("Invalid Gender value: {$value}"),
        };
    }

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'male'   => self::MALE,
            'female' => self::FEMALE,
            'kids'   => self::KIDS,
            'unisex'   => self::UNISEX,
            default  => throw new \InvalidArgumentException("Invalid Gender string: {$value}"),
        };
    }
}
