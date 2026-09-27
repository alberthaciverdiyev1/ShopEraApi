<?php

namespace Modules\Setting\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Setting\Database\Factories\SettingFactory;

class Setting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    protected $casts = [
        'seller_instructions' => 'array',
        'store_commission_percent' => 'decimal:2',
        'store_negative_balance_limit' => 'decimal:2',
        'store_late_penalty_amount' => 'decimal:2',
        'store_handover_hours' => 'integer',
        'public_low_stock_threshold' => 'integer',
    ];

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }
}
