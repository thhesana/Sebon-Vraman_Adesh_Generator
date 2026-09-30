<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsdForex extends Model
{
    protected $table = 'USDforex';

    protected $primaryKey = 'USDforexId';

    public $timestamps = false;

    protected $guarded = [];
}
