<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignationType extends Model
{
    protected $table = 'DesignationTypeMaster';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
