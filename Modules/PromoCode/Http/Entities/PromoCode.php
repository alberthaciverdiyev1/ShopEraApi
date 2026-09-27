<?php

namespace Modules\PromoCode\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PromoCode\Database\Factories\PromoCodeFactory;
use Modules\User\Http\Entities\User;

class PromoCode extends Model
{
use HasFactory,SoftDeletes;
    protected $table = 'promo_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'discount_percent',
        'user_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_percent' => 'float',
        'user_count' => 'integer',
    ];

    public static function newFactory():PromoCodeFactory
    {
        return PromoCodeFactory::new();
    }

    public function usedByUsers()
    {
        return $this->belongsToMany(
            User::class,
            'used_promo_codes',
            'promo_code_id',
            'user_id'
        )
            ->withTimestamps()
            ->withPivot('id');
    }

}
