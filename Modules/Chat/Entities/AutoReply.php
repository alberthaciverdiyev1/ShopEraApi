<?php

namespace Modules\Chat\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class AutoReply extends Model
{
    use HasTranslations, SoftDeletes;

    protected $table = 'auto_replies';

    protected $fillable = [
        'answer',
        'question',
        'embedding',
    ];

    public array $translatable = [
        'answer',
        'question',
    ];

    protected $casts = [
        'answer' => 'array',
        'question' => 'array',
    ];
}
