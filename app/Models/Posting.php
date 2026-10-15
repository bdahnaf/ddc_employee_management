<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where an employee works for a period: at an office (HQ) or on a project.
 * end_date NULL means it is the current posting. A transfer closes the
 * current row and opens a new one, so the history is never overwritten.
 */
class Posting extends Model
{
    public const HQ = 'hq';
    public const PROJECT = 'project';

    protected $fillable = ['employee_id', 'posting_type', 'office_id', 'project_id', 'role_on_posting', 'start_date', 'end_date', 'order_ref', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function scopeCurrent(Builder $query): void
    {
        $query->whereNull('end_date');
    }

    public function isHq(): bool
    {
        return $this->posting_type === self::HQ;
    }

    /** "Head Office" or the project name. */
    public function placeName(): string
    {
        return $this->isHq()
            ? ($this->office?->name ?? 'Head Office')
            : ($this->project?->name ?? 'Project');
    }
}
