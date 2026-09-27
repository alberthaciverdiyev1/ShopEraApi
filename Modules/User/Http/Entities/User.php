<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Modules\Balance\Http\Entities\Balance;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\Review;
use Modules\User\Database\Factories\UserFactory;
use Modules\User\Http\Traits\UserAccessorTrait;
use Modules\User\Http\Traits\UserRelationTrait;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, UserAccessorTrait, UserRelationTrait, HasRoles, SoftDeletes;

    protected $guard_name = 'sanctum';

//    protected $fillable = [
//        'name',
//        'email',
//        'password',
//        'email_verified_at'
//    ];

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_wholesaler' => 'boolean',
        ];
    }

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
