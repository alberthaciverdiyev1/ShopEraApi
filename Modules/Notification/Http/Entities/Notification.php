<?php

namespace Modules\Notification\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Notification\Database\Factories\NotificationFactory;
use Modules\User\Http\Entities\User;

class Notification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    // `data` is a json column, but without a cast Eloquent binds the array
    // straight into the insert and PostgreSQL raises "Array to string conversion".
    protected $casts = [
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_user', 'notification_id', 'user_id')
            ->withTimestamps();
    }


    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }
}
