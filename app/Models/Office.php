<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Head Office (and any other office). HQ postings point here. */
class Office extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'address', 'city', 'is_headquarters'];

    protected function casts(): array
    {
        return ['is_headquarters' => 'boolean'];
    }

    public function postings(): HasMany
    {
        return $this->hasMany(Posting::class);
    }
}
