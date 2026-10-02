<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;

class OtpEmail extends Model
{
    protected $guarded = [];

    protected $table = 'otp_emails';
}
