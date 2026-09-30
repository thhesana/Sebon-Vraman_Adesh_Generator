<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'CountryMaster';

    protected $primaryKey = 'Country_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'extra33percent_country' => 'integer',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'Country_id', 'Country_id');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term !== null && $term !== '' && $term !== '0') {
            $query->where('Country_name', 'like', '%'.$term.'%');
        }
    }
}
