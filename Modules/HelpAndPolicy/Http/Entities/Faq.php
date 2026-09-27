<?php

namespace Modules\HelpAndPolicy\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpAndPolicy\Database\Factories\FaqFactory;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations,SoftDeletes, HasFactory;

    protected $table = 'faqs';
    public array $translatable = ['title', 'description'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'type',
    ];

    public static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }

}
