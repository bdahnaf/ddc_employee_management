<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A top-level business unit of DDC. Has departments and leads projects. */
class Division extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('name');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(Employee::class, Department::class);
    }
}
