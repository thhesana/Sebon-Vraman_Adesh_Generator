<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'CityMaster';

    protected $primaryKey = 'City_id';

    public $timestamps = false;

    protected $guarded = [];
}
