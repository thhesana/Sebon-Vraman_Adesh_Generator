<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $table = 'Employee_Information';

    protected $primaryKey = 'EmpPersonalCode';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** TADA level, matched by level name (legacy join). */
    public function tadaLevel(): BelongsTo
    {
        return $this->belongsTo(TadaDefinerLevel::class, 'LevelName', 'TadaDefinerMasterBylevel_name');
    }

    /** Designation looked up by Designation_id. */
    public function designationType(): BelongsTo
    {
        return $this->belongsTo(DesignationType::class, 'Designation_id', 'id');
    }

    /** Designation looked up by the designation name text. */
    public function designationByName(): BelongsTo
    {
        return $this->belongsTo(DesignationType::class, 'Designation', 'designationType');
    }

    /** Domestic TADA level (per-day rate), matched by level name. */
    public function level(): BelongsTo
    {
        return $this->belongsTo(DomesticTadaDefinerLevel::class, 'LevelName', 'DomesticTadaDefinerMasterBylevel_name');
    }

    public function domesticTadas(): HasMany
    {
        return $this->hasMany(DomesticTada::class, 'EmpPersonalCode', 'EmpPersonalCode');
    }

    /** Domestic per-day rate: the level's rate, or the default when the level has none. */
    protected function domesticRate(): Attribute
    {
        return Attribute::get(fn (): float => (float) ($this->level?->tadaInNepali) ?: DomesticTada::DEFAULT_RATE);
    }

    /** Level name for display ("No Level" when the employee has none). */
    protected function levelLabel(): Attribute
    {
        return Attribute::get(fn (): string => filled($this->LevelName) ? $this->LevelName : 'No Level');
    }

    public function internationalTrips(): HasMany
    {
        return $this->hasMany(InternationalTada::class, 'EmpPersonalCode', 'EmpPersonalCode');
    }

    /** Trips starting on the employee's most recent travelDateStart. */
    public function latestInternationalTrips(): HasMany
    {
        return $this->internationalTrips()->whereRaw(
            'International_tada.travelDateStart = (SELECT MAX(t2.travelDateStart) FROM International_tada t2 WHERE t2.EmpPersonalCode = International_tada.EmpPersonalCode)'
        );
    }
}
