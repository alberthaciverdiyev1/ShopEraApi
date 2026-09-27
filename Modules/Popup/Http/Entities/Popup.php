<?php

namespace Modules\Popup\Http\Entities;

use App\Traits\ImagePath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Popup extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'popups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image',
        'show_on_home_page',
        'video'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_on_home_page' => 'boolean'
    ];

    public function getImageAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        $baseUrl = config('app.url') ?? request()->getSchemeAndHttpHost();

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }

    public function getVideoAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        $baseUrl = config('app.url') ?? request()->getSchemeAndHttpHost();

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }
}
