<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A pre-system international travel order entered by hand (table Old_International_tada).
 *
 * One row = one employee's order. Unlike International_tada, every value (position, rate, days,
 * USD total, exchange rate, NPR total, dress allowance) is typed in from the old paper order;
 * nothing is fetched or calculated. `grand_total_npr` is a computed column (total_npr + dress_allowance_npr)
 * and is never written. No mail is queued for these rows.
 *
 * All dates are Bikram Sambat strings 'YYYY-MM-DD' (columns *BS), entered with the Nepali
 * date picker. Zero-padded BS strings sort and compare correctly as text. The fiscal year is
 * not entered: it is the fiscal_year_master row whose BS range contains form_dateBS.
 */
class OldInternationalTada extends Model
{
    protected $table = 'Old_International_tada';

    protected $primaryKey = 'Old_International_tada_id';

    public $timestamps = false;

    protected $fillable = [
        'fiscal_year_master_id', 'Chalani_id', 'form_dateBS', 'EmpPersonalCode',
        'Country_id', 'City_id', 'travel_objective', 'travelDateStartBS', 'travelDateEndBS',
        'Designation_id', 'tadaverifier_id',
        'daily_rate_usd', 'total_days', 'total_usd', 'exchange_rate', 'exchange_rate_dateBS', 'total_npr',
        'DressAllowance', 'dress_allowance_npr',
        'createdBy', 'createddate', 'updatedBy', 'updateddate',
    ];

    protected $casts = [
        'fiscal_year_master_id' => 'integer',
        'Chalani_id' => 'integer',
        'Country_id' => 'integer',
        'City_id' => 'integer',
        'Designation_id' => 'integer',
        'tadaverifier_id' => 'integer',
        'daily_rate_usd' => 'float',
        'total_days' => 'float',
        'total_usd' => 'float',
        'exchange_rate' => 'float',
        'total_npr' => 'float',
        'DressAllowance' => 'boolean',
        'dress_allowance_npr' => 'float',
        'grand_total_npr' => 'float',
    ];

    protected static function booted(): void
    {
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

    /** Position (पद) at the time of travel, chosen on the form (DesignationTypeMaster). */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(DesignationType::class, 'Designation_id', 'id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(TadaVerifier::class, 'tadaverifier_id', 'tadaverifier_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_master_id', 'fiscal_year_master_id');
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    /** Dress allowance actually granted (the typed amount; DressAllowance is set when it is > 0). */
    protected function dressAmount(): Attribute
    {
        return Attribute::get(fn (): float => $this->DressAllowance ? (float) $this->dress_allowance_npr : 0.0);
    }

    /** Grand total in NPR: the computed column when loaded, otherwise daily NPR + dress. */
    protected function grandTotal(): Attribute
    {
        return Attribute::get(fn (): float => $this->grand_total_npr !== null
            ? (float) $this->grand_total_npr
            : (float) $this->total_npr + $this->dress_amount);
    }

    /** "12/2079-80" style order number for lists and the print header. */
    protected function orderNumber(): Attribute
    {
        return Attribute::get(fn (): string => trim(($this->Chalani_id ?? '-').'/'.($this->fiscalYear?->fy ?? ''), '/'));
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    /** Search by employee code/name, country, city or chalani number (bound, never interpolated). */
    public function scopeSearch(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($term, $like) {
            $q->where('EmpPersonalCode', 'like', $like)
                ->orWhere('travel_objective', 'like', $like)
                ->orWhereHas('employee', fn (Builder $e) => $e->where('EmpName', 'like', $like))
                ->orWhereHas('country', fn (Builder $c) => $c->where('Country_name', 'like', $like))
                ->orWhereHas('city', fn (Builder $c) => $c->where('City_name', 'like', $like));

            if (ctype_digit($term)) {
                $q->orWhere('Chalani_id', (int) $term);
            }
        });
    }

    /**
     * International TADA report filters, same keys and "empty / '0' = no filter" rules as
     * InternationalTada::scopeFilter. Designation matches the position chosen on the record;
     * dates compare the BS columns. Old records have no batch, so a batch filter excludes them all.
     */
    public function scopeReportFilter(Builder $query, array $f): void
    {
        $has = fn ($v) => $v !== null && $v !== '' && $v !== '0';

        $query
            ->when($has($f['batch'] ?? ''), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($has($f['fy'] ?? ''), fn ($q) => $q->where('fiscal_year_master_id', $f['fy']))
            ->when($has($f['emp'] ?? ''), fn ($q) => $q->where('EmpPersonalCode', $f['emp']))
            ->when($has($f['country'] ?? ''), fn ($q) => $q->where('Country_id', $f['country']))
            ->when($has($f['city'] ?? ''), fn ($q) => $q->where('City_id', $f['city']))
            ->when($has($f['designation'] ?? ''), fn ($q) => $q->whereHas('designation', fn ($d) => $d->where('designationType', $f['designation'])))
            ->when(($f['dress'] ?? '') !== '', fn ($q) => $q->where('DressAllowance', (int) $f['dress']))
            ->when(($f['extra33'] ?? '') === '1', fn ($q) => $q->whereHas('country', fn ($c) => $c->where('extra33percent_country', 1)))
            ->when(($f['extra33'] ?? '') === '0', fn ($q) => $q->whereDoesntHave('country', fn ($c) => $c->where('extra33percent_country', 1)))
            ->when($has($f['date_from'] ?? ''), fn ($q) => $q->where('travelDateStartBS', '>=', $f['date_from']))
            ->when($has($f['date_to'] ?? ''), fn ($q) => $q->where('travelDateEndBS', '<=', $f['date_to']));
    }

    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('travelDateStartBS')->orderByDesc('Old_International_tada_id');
    }
}
