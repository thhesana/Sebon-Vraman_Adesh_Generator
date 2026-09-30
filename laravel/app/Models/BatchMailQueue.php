<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchMailQueue extends Model
{
    protected $table = 'BatchMailQueue';

    protected $primaryKey = 'Queue_id';

    public $timestamps = false;

    protected $guarded = [];
}
