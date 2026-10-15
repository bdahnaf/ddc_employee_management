<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A team inside a division. Employees belong to one department. */
class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['division_id', 'name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class)->withTrashed();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
