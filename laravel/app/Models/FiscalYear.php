<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $table = 'fiscal_year_master';

    protected $primaryKey = 'fiscal_year_master_id';

    public $timestamps = false;

    protected $guarded = [];

    /** The ACTIVE fiscal year whose date range contains today (server date). */
    public static function current(): ?static
    {
        return static::query()
            ->whereRaw('CAST(GETDATE() AS DATE) BETWEEN CAST(fy_startdate AS DATE) AND CAST(fy_enddate AS DATE)')
            ->where('fy_status', 'ACTIVE')
            ->first();
    }
}
