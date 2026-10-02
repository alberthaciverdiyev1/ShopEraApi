<?php

namespace Modules\Chat\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class Conversation extends Model
{
    protected $guarded = [];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
