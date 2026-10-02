<?php

namespace Modules\Manager\Enums;

enum OwnerStatus: string
{
    case ACTIVE = 'active';
    case TRIAL = 'trial';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktiv',
            self::TRIAL => 'Sınaq',
            self::SUSPENDED => 'Dayandırılıb',
            self::CANCELLED => 'Ləğv edilib',
        };
    }
}
