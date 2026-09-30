<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * One employee's row of an international TADA batch (table International_tada).
 *
 * `totalday` is a COMPUTED column in SQL Server: it is deliberately not fillable and is
 * never written; use the `days` accessor instead.
 */
class InternationalTada extends Model
{
    protected $table = 'International_tada';

    protected $primaryKey = 'International_tada_id';

    public $timestamps = false;

    protected $fillable = [
        'Batch_id', 'fiscal_year_master_id', 'Chalani_id', 'form_date', 'EmpPersonalCode',
        'Country_id', 'City_id', 'travel_objective', 'travelDateStart', 'travelDateEnd',
        'TadaDefinerMasterBylevel_id', 'totalUSdrecevid', 'DressAllowance', 'createdBy',
        'createddate', 'tadaverifier_id',
    ];

    protected $casts = [
        'fiscal_year_master_id' => 'integer',
        'Chalani_id' => 'integer',
        'Country_id' => 'integer',
        'City_id' => 'integer',
        'TadaDefinerMasterBylevel_id' => 'integer',
        'tadaverifier_id' => 'integer',
        'form_date' => 'date:Y-m-d',
        'travelDateStart' => 'date:Y-m-d',
        'travelDateEnd' => 'date:Y-m-d',
        'totalUSdrecevid' => 'float',
        'DressAllowance' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Legacy behaviour: createddate is the database server time.
        static::creating(function (self $row) {
            $row->createddate ??= DB::raw('GETDATE()');
        });
    }

    // ── Relationships ───────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'EmpPersonalCode', 'EmpPersonalCode');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'Country_id', 'Country_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'City_id', 'City_id');
    }

    public function tadaLevel(): BelongsTo
    {
        return $this->belongsTo(TadaDefinerLevel::class, 'TadaDefinerMasterBylevel_id', 'TadaDefinerMasterBylevel_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(TadaVerifier::class, 'tadaverifier_id', 'tadaverifier_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_master_id', 'fiscal_year_master_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdBy', 'user_id');
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    /** Trip length with the half-day rule ((days between + 1) - 0.5); null when dates are missing. */
    protected function days(): Attribute
    {
        return Attribute::get(fn () => $this->travelDateStart && $this->travelDateEnd
            ? static::daysBetween($this->travelDateStart, $this->travelDateEnd)
            : null);
    }

    /** Daily USD rate of the employee's level incl. the 33% extra-country rule; null without a level. */
    protected function dailyRate(): Attribute
    {
        return Attribute::get(function () {
            $rate = $this->tadaLevel?->tadaInUSD;
            if ($rate === null) {
                return null;
            }

            return static::dailyRateFor((float) $rate, (int) $this->country?->extra33percent_country === 1);
        });
    }

    /** Days x daily rate (incl. the 33% extra); null when either is unknown. */
    protected function usdAmount(): Attribute
    {
        return Attribute::get(fn () => $this->days !== null && $this->daily_rate !== null
            ? round($this->days * $this->daily_rate, 6)
            : null);
    }

    /** Amount shown on reports: the stored figure, else the computed one, else 0. */
    protected function usdTotal(): Attribute
    {
        return Attribute::get(fn () => (float) ($this->totalUSdrecevid ?? $this->usd_amount ?? 0));
    }

    /** Dress allowance in NPR (10,000 for level ids 5 and 11, otherwise 8,000; 0 when not granted). */
    protected function dressAmount(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->DressAllowance) {
                return 0;
            }

            return in_array((int) $this->employee?->LevelName_id, [5, 11], true) ? 10000 : 8000;
        });
    }

    /** Days elapsed since the trip started (history page); null without a start date. */
    protected function daysSinceTravel(): Attribute
    {
        return Attribute::get(fn () => $this->travelDateStart
            ? (int) $this->travelDateStart->startOfDay()->diffInDays(Carbon::today())
            : null);
    }

    /** Employee row of the add/edit forms' JavaScript. */
    public function toFormEmployee(): array
    {
        return [
            'code' => $this->EmpPersonalCode,
            'name' => $this->employee?->EmpName,
            'level' => $this->tadaLevel?->TadaDefinerMasterBylevel_name ?: 'No TADA Level',
            'usd' => $this->tadaLevel?->tadaInUSD ?: 0,
            'tadaId' => $this->TadaDefinerMasterBylevel_id,
            'dressAllowance' => (bool) $this->DressAllowance,
        ];
    }

    // ── Domain rules ────────────────────────────────────────────────────────

    /** Half-day rule: (days between + 1) - 0.5. */
    public static function daysBetween($start, $end): float
    {
        $days = abs((int) Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay()));

        return $days + 1 - 0.5;
    }

    /** Daily rate incl. the 33% extra-country rule (rounded to hide float noise). */
    public static function dailyRateFor(float $usdPerDay, bool $extra33): float
    {
        return $extra33 ? round($usdPerDay + ($usdPerDay * 0.33), 6) : $usdPerDay;
    }

    /** Value stored in totalUSdrecevid: days x level rate, WITHOUT the 33% extra (legacy behaviour). */
    public static function storedUsd(float $days, $usdPerDay): float
    {
        return $days * (float) $usdPerDay;
    }

    /** Next batch id from dbo.fn_GenerateBatchId(), falling back to MAX(Batch_id) + 1. */
    public static function nextBatchId(): string
    {
        try {
            return DB::selectOne('SELECT dbo.fn_GenerateBatchId() AS Batch_id')->Batch_id;
        } catch (\Throwable $e) {
            try {
                $maxBatch = static::query()->max('Batch_id');
            } catch (\Throwable $e2) {
                return 'BATCH001';
            }
            if (empty($maxBatch)) {
                return 'BATCH001';
            }

            return 'BATCH'.str_pad((int) substr($maxBatch, 5) + 1, 3, '0', STR_PAD_LEFT);
        }
    }

    /** Next chalani number inside a fiscal year (1 when unknown). */
    public static function nextChalaniNumber($fiscalYearId): int
    {
        if (empty($fiscalYearId)) {
            return 1;
        }
        try {
            return (int) static::query()->where('fiscal_year_master_id', $fiscalYearId)->max('Chalani_id') + 1;
        } catch (\Throwable $e) {
            report($e);

            return 1;
        }
    }

    /** "Chalani #: 5" or "Chalani #: 5 - 8". */
    public static function chalaniRange(?int $first, ?int $last): string
    {
        return $first == $last ? "Chalani #: {$first}" : "Chalani #: {$first} - {$last}";
    }

    /** USD -> NPR rate whose conversion date equals this row's form date. */
    public function usdRate(): ?UsdForex
    {
        return $this->form_date ? UsdForex::query()->whereDate('conversion_date', $this->form_date)->first() : null;
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeBatch(Builder $query, string $batchId): void
    {
        $query->where('Batch_id', $batchId);
    }

    /** Paged batch listing order: newest first. */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('createddate')->orderByDesc('Batch_id');
    }

    /** Report filters; semantics identical to the legacy WHERE builder (PHP empty() rules). */
    public function scopeFilter(Builder $query, array $f): void
    {
        $has = fn ($v) => $v !== null && $v !== '' && $v !== '0';

        $query
            ->when($has($f['fy']), fn ($q) => $q->where('fiscal_year_master_id', $f['fy']))
            ->when($has($f['emp']), fn ($q) => $q->where('EmpPersonalCode', $f['emp']))
            ->when($has($f['country']), fn ($q) => $q->where('Country_id', $f['country']))
            ->when($has($f['city']), fn ($q) => $q->where('City_id', $f['city']))
            ->when($has($f['designation']), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('Designation', $f['designation'])))
            ->when($has($f['batch']), fn ($q) => $q->where('Batch_id', $f['batch']))
            ->when($f['dress'] !== '', fn ($q) => $q->where('DressAllowance', (int) $f['dress']))
            ->when($f['extra33'] === '1', fn ($q) => $q->whereHas('country', fn ($c) => $c->where('extra33percent_country', 1)))
            ->when($f['extra33'] === '0', fn ($q) => $q->whereDoesntHave('country', fn ($c) => $c->where('extra33percent_country', 1)))
            ->when($has($f['date_from']), fn ($q) => $q->where('travelDateStart', '>=', $f['date_from']))
            ->when($has($f['date_to']), fn ($q) => $q->where('travelDateEnd', '<=', $f['date_to']));
    }

    /**
     * History page: every employee with their latest trip(s) (those starting on the employee's
     * newest travelDateStart), optionally searched by code, name, country or batch (bound, never
     * interpolated). Employees without trips yield a blank row. Rows are unsaved InternationalTada
     * models with `employee` (and `country`) loaded.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function latestVisits(string $search = ''): \Illuminate\Support\Collection
    {
        $like = '%'.$search.'%';

        return Employee::query()
            ->with('latestInternationalTrips.country')
            ->where(fn ($q) => $q
                ->where('EmpPersonalCode', 'like', $like)
                ->orWhere('EmpName', 'like', $like)
                ->orWhereHas('latestInternationalTrips', fn ($t) => $t
                    ->where('Batch_id', 'like', $like)
                    ->orWhereHas('country', fn ($c) => $c->where('Country_name', 'like', $like))))
            ->orderBy('EmpPersonalCode')
            ->get()
            ->flatMap(fn (Employee $e) => ($e->latestInternationalTrips->isEmpty() ? collect([new static]) : $e->latestInternationalTrips)
                ->each(fn (self $trip) => $trip->setRelation('employee', $e))
                ->values());
    }

    // ── Lookups for the report filter form ──────────────────────────────────

    /** @return array<string, \Illuminate\Support\Collection> */
    public static function reportFilterOptions(): array
    {
        $trips = static::query();

        return [
            'fyList' => FiscalYear::query()->orderByDesc('fy')->get(['fiscal_year_master_id', 'fy']),
            'empList' => Employee::query()->whereIn('EmpPersonalCode', (clone $trips)->select('EmpPersonalCode'))
                ->orderBy('EmpName')->get(['EmpPersonalCode', 'EmpName']),
            'countryList' => Country::query()->whereIn('Country_id', (clone $trips)->select('Country_id'))
                ->orderBy('Country_name')->get(['Country_id', 'Country_name']),
            'cityList' => City::query()->whereIn('City_id', (clone $trips)->select('City_id'))
                ->orderBy('City_name')->get(['City_id', 'City_name']),
            'desigList' => Employee::query()->whereIn('EmpPersonalCode', (clone $trips)->select('EmpPersonalCode'))
                ->whereNotNull('Designation')->distinct()->orderBy('Designation')->pluck('Designation'),
        ];
    }
}
