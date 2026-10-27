<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee's pay breakdown from a date. effective_to NULL = current.
 * Sensitive: only HR and the employee themselves may see it.
 */
class SalaryStructure extends Model
{
    public const PARTS = ['basic' => 'Basic', 'house_rent' => 'House rent', 'medical' => 'Medical', 'conveyance' => 'Conveyance', 'other_allowance' => 'Other allowance'];

    protected $fillable = ['employee_id', 'basic', 'house_rent', 'medical', 'conveyance', 'other_allowance', 'gross', 'effective_from', 'effective_to', 'created_by'];

    protected function casts(): array
    {
        return [
            'basic' => 'decimal:2', 'house_rent' => 'decimal:2', 'medical' => 'decimal:2',
            'conveyance' => 'decimal:2', 'other_allowance' => 'decimal:2', 'gross' => 'decimal:2',
            'effective_from' => 'date', 'effective_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Gross = sum of the parts. */
    public static function grossOf(array $parts): float
    {
        return round(array_sum(array_map(fn ($k) => (float) ($parts[$k] ?? 0), array_keys(self::PARTS))), 2);
    }
}
