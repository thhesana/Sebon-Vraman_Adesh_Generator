<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomesticTadaDefinerLevel extends Model
{
    protected $table = 'DomesticTadaDefinerMasterBylevel';

    protected $primaryKey = 'DomesticTadaDefinerMasterBylevel_id';

    public $timestamps = false;

    protected $guarded = [];
}
