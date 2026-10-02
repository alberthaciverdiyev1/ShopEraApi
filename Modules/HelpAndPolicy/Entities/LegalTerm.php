<?php

namespace Modules\HelpAndPolicy\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpAndPolicy\Database\Factories\LegalTermsFactory;
use Spatie\Translatable\HasTranslations;

class LegalTerm extends Model
{
    use HasFactory,HasTranslations, SoftDeletes;

    protected $table = 'legal_terms_policies';

    protected $translatable = ['html'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'html',
    ];

    protected $casts = [
        'html' => 'array',
    ];

    public static function newFactory(): LegalTermsFactory
    {
        return LegalTermsFactory::new();
    }
}
