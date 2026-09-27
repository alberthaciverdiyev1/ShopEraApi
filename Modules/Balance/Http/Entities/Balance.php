<?php

namespace Modules\Balance\Http\Entities;

use App\Enums\BalanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\User\Http\Entities\User;

class Balance extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'balances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 'amount', 'type','note','transaction_e_point','transaction_order'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => BalanceType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
