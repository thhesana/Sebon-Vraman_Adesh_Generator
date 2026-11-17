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

    /**
     * The fiscal year whose BS range (fy_startdateBS..fy_enddateBS) contains a BS date 'YYYY-MM-DD'.
     * Zero-padded BS strings compare correctly as text. Status is ignored (old years may be INACTIVE).
     */
    public static function forBsDate(string $bsDate): ?static
    {
        return static::query()
            ->where('fy_startdateBS', '<=', $bsDate)
            ->where('fy_enddateBS', '>=', $bsDate)
            ->orderByDesc('fy_startdateBS')
            ->first();
    }
}
