<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelType extends Model
{
    protected $table = 'TraveltypeMaster';

    protected $primaryKey = 'TadaTypeMaster_id';

    public $timestamps = false;

    protected $guarded = [];
}
