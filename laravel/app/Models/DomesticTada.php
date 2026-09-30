<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One employee's row of a domestic TADA "batch" (batches are identified by DBATCH### ids).
 *
 * `domestic_totalday` is a computed column in the database: it is never written.
 */
class DomesticTada extends Model
{
    /** Per-day rate used when an employee's level defines none. */
    public const DEFAULT_RATE = 2400;

    /** Multiplier applied when the 20% extra is granted. */
    public const EXTRA_MULTIPLIER = 1.20;

    protected $table = 'DomesticTada';

    protected $primaryKey = 'domestic_tada_id';

    public $timestamps = false;

    protected $fillable = [
        'domestic_Batch_id',
        'domestic_Chalani_id',
        'domestic_form_date',
        'EmpPersonalCode',
        'District_id',
        'domestic_isTwentyPercentExtra',
        'TadaTypeMaster_id',
        'domestic_travel_objective',
        'domestic_travelDateStart',
        'domestic_travelDateEnd',
        'domestic_tada',
        'domestic_createdBy',
        'domestic_createddate',
        'fiscal_year_master_id',
        'tadaverifier_id',
    ];

    protected function casts(): array
    {
        return [
            'domestic_Chalani_id' => 'integer',
            'domestic_form_date' => 'date:Y-m-d',
            'District_id' => 'integer',
            'domestic_isTwentyPercentExtra' => 'boolean',
            'TadaTypeMaster_id' => 'integer',
            'domestic_travelDateStart' => 'date:Y-m-d',
            'domestic_travelDateEnd' => 'date:Y-m-d',
            'domestic_tada' => 'decimal:2',
            'domestic_totalday' => 'integer',
            'fiscal_year_master_id' => 'integer',
            'tadaverifier_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Creation time comes from the database clock (as the legacy app did).
        static::creating(function (self $row) {
            $row->domestic_createddate ??= DB::raw('GETDATE()');
        });
    }

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'EmpPersonalCode', 'EmpPersonalCode');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'District_id', 'District_id');
    }

    public function travelType(): BelongsTo
    {
        return $this->belongsTo(TravelType::class, 'TadaTypeMaster_id', 'TadaTypeMaster_id');
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
        return $this->belongsTo(User::class, 'domestic_createdBy', 'user_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeBatch(Builder $query, string $batchId): void
    {
        $query->where('domestic_Batch_id', $batchId);
    }

    public function scopeForFiscalYear(Builder $query, int|string $fiscalYearId): void
    {
        $query->where('fiscal_year_master_id', $fiscalYearId);
    }

    /** Rows whose employee name contains the term (no-op for an empty term). */
    public function scopeEmployeeNameLike(Builder $query, string $term): void
    {
        $query->when($term !== '', fn (Builder $q) => $q->whereHas(
            'employee',
            fn (Builder $e) => $e->where('EmpName', 'like', '%'.$term.'%')
        ));
    }

    /**
     * One line per batch, newest first (list page pagination is by batch).
     * The MIN() ordering has no Eloquent equivalent, hence the raw fragments.
     */
    public function scopeBatchSummaries(Builder $query, string $search = ''): void
    {
        $query->employeeNameLike($search)
            ->select('domestic_Batch_id')
            ->selectRaw('MIN(domestic_createddate) AS batch_created_date')
            ->groupBy('domestic_Batch_id')
            ->orderByRaw('MIN(domestic_createddate) DESC, domestic_Batch_id DESC');
    }

    /** All rows of the given batches for the list page, batch by batch. */
    public function scopeListing(Builder $query, iterable $batchIds): void
    {
        $query->with(['employee', 'district'])
            ->whereIn('domestic_Batch_id', collect($batchIds)->all())
            ->orderByDesc('domestic_Batch_id')
            ->orderByDesc('domestic_createddate')
            ->orderBy('domestic_tada_id');
    }

    /**
     * Report rows. Legacy semantics: an empty filter value (including "0") means "no filter".
     *
     * @param  array{fy?:string,emp?:string,district?:string,designation?:string}  $filters
     */
    public function scopeReport(Builder $query, array $filters): void
    {
        $query->with(['employee.level', 'employee.designationByName', 'district', 'travelType', 'fiscalYear', 'creator'])
            ->when(! empty($filters['fy']), fn (Builder $q) => $q->forFiscalYear($filters['fy']))
            ->when(! empty($filters['emp']), fn (Builder $q) => $q->where('EmpPersonalCode', $filters['emp']))
            ->when(! empty($filters['district']), fn (Builder $q) => $q->where('District_id', $filters['district']))
            ->when(! empty($filters['designation']), fn (Builder $q) => $q->whereHas(
                'employee',
                fn (Builder $e) => $e->where('Designation', $filters['designation'])
            ))
            ->orderByDesc('domestic_tada_id');
    }

    /** Rows of one batch with everything the printed order needs. */
    public function scopeForPrint(Builder $query, string $batchId): void
    {
        $query->batch($batchId)
            ->with(['employee.level', 'employee.designationByName', 'district', 'travelType', 'fiscalYear', 'verifier'])
            ->orderBy('domestic_Chalani_id');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────────

    /** "YES" / "NO" label of the 20% extra flag. */
    protected function extraLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->domestic_isTwentyPercentExtra ? 'YES' : 'NO');
    }

    /** The employee level's per-day rate (0 when unknown), without the 20% extra. */
    protected function levelRate(): Attribute
    {
        return Attribute::get(fn (): float => (float) ($this->employee?->level?->tadaInNepali ?? 0));
    }

    /** Per-day rate as shown on reports and orders (includes the 20% extra when granted). */
    protected function displayRate(): Attribute
    {
        return Attribute::get(fn (): float => $this->domestic_isTwentyPercentExtra
            ? $this->level_rate * self::EXTRA_MULTIPLIER
            : $this->level_rate);
    }

    /** Per-day rate for display (see displayRate). */
    protected function displayRateLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::formatAmount($this->display_rate));
    }

    /** Total TADA with two decimals only when needed. */
    protected function tadaLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::formatAmount((float) $this->domestic_tada));
    }

    /** Whole numbers without decimals, everything else with two. */
    public static function formatAmount(float $amount): string
    {
        return number_format($amount, floor($amount) == $amount ? 0 : 2);
    }

    // ─── Domain rules ───────────────────────────────────────────────────────────

    /** Next batch id: DBATCH001, DBATCH002, ... */
    public static function nextBatchId(): string
    {
        $max = static::query()->max('domestic_Batch_id');

        return 'DBATCH'.Str::padLeft((string) (($max ? (int) substr($max, 6) : 0) + 1), 3, '0');
    }

    /** Next chalani number: global by default, per fiscal year when one is given. */
    public static function nextChalani(?int $fiscalYearId = null): int
    {
        return (int) static::query()
            ->when($fiscalYearId, fn (Builder $q, int $fy) => $q->forFiscalYear($fy))
            ->max('domestic_Chalani_id') + 1;
    }

    /** Inclusive number of days between two dates (both the start and the end day count). */
    public static function inclusiveDays(CarbonInterface|string $start, CarbonInterface|string $end): int
    {
        return (int) Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay(), false) + 1;
    }

    /** TADA amount: days x per-day rate, plus 20% when the extra is granted. */
    public static function calculateTada(int $days, float $ratePerDay, bool $twentyPercentExtra): float
    {
        $total = $days * $ratePerDay;

        return $twentyPercentExtra ? $total * self::EXTRA_MULTIPLIER : $total;
    }
}
