<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TadaVerifier extends Model
{
    protected $table = 'tadaverifier';

    protected $primaryKey = 'tadaverifier_id';

    public $timestamps = false;

    protected $guarded = [];
}
