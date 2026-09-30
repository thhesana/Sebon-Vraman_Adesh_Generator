<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'DistrictMaster';

    protected $primaryKey = 'District_id';

    public $timestamps = false;

    protected $guarded = [];
}
