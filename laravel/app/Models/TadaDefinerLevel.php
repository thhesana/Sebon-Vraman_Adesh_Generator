<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TadaDefinerLevel extends Model
{
    protected $table = 'TadaDefinerMasterBylevel';

    protected $primaryKey = 'TadaDefinerMasterBylevel_id';

    public $timestamps = false;

    protected $guarded = [];

    /** 'Chairman' first, then the highest USD allowance. */
    public function scopeChairmanFirst(Builder $query): void
    {
        $query->orderByRaw("CASE WHEN TadaDefinerMasterBylevel_name = 'Chairman' THEN 0 ELSE 1 END")
            ->orderByDesc('tadaInUSD');
    }
}
