<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A job title, e.g. Senior Engineer. */
class Designation extends Model
{
    protected $fillable = ['title', 'grade'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
