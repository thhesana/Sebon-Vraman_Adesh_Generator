<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $table = 'fiscal_year_master';

    protected $primaryKey = 'fiscal_year_master_id';

    public $timestamps = false;

    protected $guarded = [];
}
