<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Http\Entities\User;

class StoreWithdrawal extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function walletTransaction()
    {
        return $this->belongsTo(StoreWalletTransaction::class);
    }

    /** A request still holding money in reserve. */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }
}
