<?php

namespace Modules\Brand\Http\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
public function toArray($request){
    return [
        'id'=>$this->id,
        'name'=>$this->name,
        'image'=>$this->image,
        'is_active'=>$this->is_active,
        'sort_order'=>$this->sort_order
    ];
}
}
