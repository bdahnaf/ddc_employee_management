<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * One line in the audit trail.
 *
 *   ActivityLog::record('user.created', "Created account for {$user->email}", $user);
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'description', 'ip_address'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function record(string $action, string $description, ?Model $subject = null, ?User $by = null): self
    {
        $by ??= Auth::user();

        return static::create([
            'user_id' => $by?->id,
            'user_name' => $by?->name,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
