<?php

namespace Modules\Delivery\Http\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function findMatching(?string $value, bool $onlyActive = true): ?self
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return static::query()
            ->when($onlyActive, fn(Builder $query) => $query->active())
            ->where(function (Builder $query) use ($value) {
                $query
                    ->whereRaw('LOWER(key) = ?', [mb_strtolower($value)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
            })
            ->first();
    }
}
