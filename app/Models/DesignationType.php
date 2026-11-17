<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignationType extends Model
{
    protected $table = 'DesignationTypeMaster';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['designationType', 'designationTypeInNepali'];

    /** Employees holding this position (linked by name, as the employee forms store it). */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'Designation', 'designationType');
    }
}
