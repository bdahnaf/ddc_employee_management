<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A project site. Led by one division; staff from any department can be posted to it. */
class Project extends Model
{
    use SoftDeletes;

    public const STATUSES = ['planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed'];

    protected $fillable = ['division_id', 'name', 'code', 'client', 'location', 'start_date', 'end_date', 'status', 'description'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class)->withTrashed();
    }

    public function postings(): HasMany
    {
        return $this->hasMany(Posting::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
