<?php

namespace Modules\Product\Http\Entities;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Database\Factories\ReviewFactory;
use Modules\User\Http\Entities\User;

class Review extends Model
{
    use HasFactory;

    protected $table = 'product_reviews';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
           // 'status' => ReviewStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }
//    protected function status(): Attribute
//    {
//        return Attribute::make(
//            get: function ($value) {
//                if (is_numeric($value)) {
//                    return ReviewStatus::tryFrom((int)$value);
//                }
//
//                return match (strtolower((string)$value)) {
//                    'pending'  => ReviewStatus::PENDING,
//                    'approved' => ReviewStatus::APPROVED,
//                    'rejected' => ReviewStatus::REJECTED,
//                    default    => null,
//                };
//            }
//        );
//    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) return ReviewStatus::PENDING;

                if (is_numeric($value)) {
                    return ReviewStatus::tryFrom((int)$value) ?? ReviewStatus::PENDING;
                }

                return match (strtolower((string)$value)) {
                    'pending'  => ReviewStatus::PENDING,
                    'approved' => ReviewStatus::APPROVED,
                    'rejected' => ReviewStatus::REJECTED,
                    default    => ReviewStatus::PENDING,
                };
            },
            set: fn ($value) => $value instanceof ReviewStatus ? $value->value : $value
        );
    }
}
