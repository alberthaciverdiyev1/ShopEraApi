<?php

namespace App\Enums;

enum BalanceType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';

    case REFUND = 'refund';
    case BONUS = 'bonus';
    case WAITING = 'waiting';
    case REFERRAL = 'referral';
}
