<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'CountryMaster';

    protected $primaryKey = 'Country_id';

    public $timestamps = false;

    protected $guarded = [];
}
