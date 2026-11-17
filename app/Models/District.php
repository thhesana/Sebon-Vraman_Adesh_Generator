<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $table = 'DistrictMaster';

    protected $primaryKey = 'District_id';

    public $timestamps = false;

    protected $guarded = [];

    public function domesticTadas(): HasMany
    {
        return $this->hasMany(DomesticTada::class, 'District_id', 'District_id');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('District_name', 'like', $like)
                ->orWhere('District_name_nepali', 'like', $like);
        });
    }
}
