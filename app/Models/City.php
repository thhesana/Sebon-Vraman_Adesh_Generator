<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $table = 'CityMaster';

    protected $primaryKey = 'City_id';

    public $timestamps = false;

    protected $guarded = [];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'Country_id', 'Country_id');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '' || $term === '0') {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('City_name', 'like', $like)
                ->orWhereHas('country', fn (Builder $c) => $c->where('Country_name', 'like', $like));
        });
    }
}
