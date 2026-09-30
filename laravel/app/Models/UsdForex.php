<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UsdForex extends Model
{
    protected $table = 'USDforex';

    protected $primaryKey = 'USDforexId';

    public $timestamps = false;

    protected $guarded = [];

    /** Most recent conversion dates first. */
    public function scopeLatestRates(Builder $query, int $limit = 12): void
    {
        $query->orderByDesc('conversion_date')->limit($limit);
    }

    /**
     * Insert or update the rate for a date through the sp_UpsertUSDForex stored procedure
     * (Eloquent cannot express it). Returns the procedure's single result row.
     *
     * @return array<string,mixed>
     */
    public static function upsertRate(string $date, float $amount): array
    {
        $rows = DB::select('EXEC sp_UpsertUSDForex ?, ?', [date('Y-m-d', strtotime($date)), $amount]);

        return (array) ($rows[0] ?? []);
    }
}
