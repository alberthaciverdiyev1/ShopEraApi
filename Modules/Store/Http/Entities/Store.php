<?php

namespace Modules\Store\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Http\Entities\Product;
use Modules\User\Http\Entities\User;

class Store extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    // DB defaults are not hydrated onto a freshly created model, so a store
    // returned straight from register() reported a null status and the app
    // showed "unknown" instead of "awaiting approval".
    protected $attributes = [
        'status' => 'pending',
        'is_active' => false,
        'is_trusted' => false,
        'balance' => 0,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_trusted' => 'boolean',
        'balance' => 'decimal:2',
        'commission_percent_override' => 'decimal:2',
        'negative_balance_limit_override' => 'decimal:2',
        'instructions_accepted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'changes_requested_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    protected $hidden = ['identity_front_path', 'identity_back_path'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(StoreWalletTransaction::class);
    }

    public function settlements()
    {
        return $this->hasMany(StoreOrderSettlement::class);
    }

    public function fulfillments()
    {
        return $this->hasMany(StoreOrderFulfillment::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(StoreWithdrawal::class);
    }
}
