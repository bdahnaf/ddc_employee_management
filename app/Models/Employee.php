<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A DDC staff member.
 *
 * Privacy: the columns in SENSITIVE (and all salary data) are only shown to HR
 * and to the employee themselves. The check is the `view-sensitive` gate;
 * the directory never filters or sorts by them for other users.
 */
class Employee extends Model
{
    use SoftDeletes;

    public const STATUSES = ['active' => 'Active', 'on_leave' => 'On leave', 'resigned' => 'Resigned', 'retired' => 'Retired'];

    public const EMPLOYMENT_TYPES = ['permanent' => 'Permanent', 'contractual' => 'Contractual', 'project' => 'Project-based'];

    public const GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /** Personal details hidden from everyone except HR and the person themselves. */
    public const SENSITIVE = [
        'personal_email', 'personal_phone', 'date_of_birth', 'gender', 'blood_group',
        'nid_no', 'tin_no', 'passport_no', 'present_address', 'permanent_address',
        'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_phone',
        'bank_name', 'bank_account_no',
    ];

    protected $fillable = [
        'user_id', 'employee_code', 'name', 'photo_path', 'designation_id', 'department_id',
        'status', 'employment_type', 'joining_date', 'office_email', 'office_phone',
        ...self::SENSITIVE,
    ];

    protected function casts(): array
    {
        return ['joining_date' => 'date', 'date_of_birth' => 'date'];
    }

    // ---------- Relations ----------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function postings(): HasMany
    {
        return $this->hasMany(Posting::class)->orderByDesc('start_date')->orderByDesc('id');
    }

    public function currentPosting(): HasOne
    {
        return $this->hasOne(Posting::class)->whereNull('end_date')->latestOfMany('start_date');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(SalaryStructure::class)->orderByDesc('effective_from');
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)->whereNull('effective_to')->latestOfMany('effective_from');
    }

    // ---------- Scopes used by the directory ----------

    /** Employees whose current posting is at HQ (any office, or one office). */
    public function scopeAtHq(Builder $query, ?int $officeId = null): void
    {
        $query->whereHas('postings', fn ($p) => $p->whereNull('end_date')->where('posting_type', Posting::HQ)
            ->when($officeId, fn ($p) => $p->where('office_id', $officeId)));
    }

    /** Employees currently posted to a project (any, or one). */
    public function scopeOnProject(Builder $query, ?int $projectId = null): void
    {
        $query->whereHas('postings', fn ($p) => $p->whereNull('end_date')->where('posting_type', Posting::PROJECT)
            ->when($projectId, fn ($p) => $p->where('project_id', $projectId)));
    }

    public function scopeInDivision(Builder $query, int $divisionId): void
    {
        $query->whereHas('department', fn ($d) => $d->where('division_id', $divisionId));
    }

    // ---------- Helpers ----------

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: '?';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /** e.g. "4 years 3 months" */
    public function serviceLength(): ?string
    {
        if (! $this->joining_date) {
            return null;
        }
        $diff = $this->joining_date->diff(now());
        $parts = [];
        if ($diff->y) {
            $parts[] = $diff->y.' '.str('year')->plural($diff->y);
        }
        if ($diff->m) {
            $parts[] = $diff->m.' '.str('month')->plural($diff->m);
        }

        return $parts ? implode(' ', $parts) : 'Less than a month';
    }
}
