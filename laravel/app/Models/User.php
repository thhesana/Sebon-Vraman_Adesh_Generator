<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'Users';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $guarded = [];
}
