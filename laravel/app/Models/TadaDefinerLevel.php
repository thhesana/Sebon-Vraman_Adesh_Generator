<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TadaDefinerLevel extends Model
{
    protected $table = 'TadaDefinerMasterBylevel';

    protected $primaryKey = 'TadaDefinerMasterBylevel_id';

    public $timestamps = false;

    protected $guarded = [];
}
