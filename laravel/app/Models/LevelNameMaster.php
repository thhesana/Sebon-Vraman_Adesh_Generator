<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelNameMaster extends Model
{
    protected $table = 'LevelNameMaster';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
