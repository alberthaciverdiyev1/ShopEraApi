<?php

namespace Modules\User\Http\Traits;

use Illuminate\Support\Facades\DB;

trait UserAccessorTrait
{
    public function getTotalBalanceAttribute(): float
    {
        return $this->balance()
            ->whereNotIn('type', ['waiting'])
            ->sum(DB::raw("
                CASE
                    WHEN type IN ('deposit','refund','bonus','referral') THEN amount
                    ELSE -amount
                END
            "));
    }
}
