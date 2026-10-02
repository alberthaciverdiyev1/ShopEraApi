<?php

namespace Modules\Manager\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for every control-plane entity. These live in the central "main"
 * database (the `control` connection) and must never touch a tenant database.
 */
abstract class ControlModel extends Model
{
    protected $connection = 'control';
}
