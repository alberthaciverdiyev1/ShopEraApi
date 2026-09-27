<?php

namespace Modules\Balance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            "id"=> $this->id,
            "amount"=>$this->amount,
            "type"=>$this->type,
            "note"=>$this->note,
            "created_at"=>$this->created_at->format('Y-m-d H:i:s'),

        ];
    }

}
