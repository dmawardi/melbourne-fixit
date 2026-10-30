<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use Carbon\CarbonImmutable;
use Database\Factories\JobRequestActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $job_request_id
 * @property int|null $user_id
 * @property ActivityEvent $event
 * @property array<string, mixed>|null $properties
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['event', 'properties'])]
class JobRequestActivity extends Model
{
    /** @use HasFactory<JobRequestActivityFactory> */
    use HasFactory;

    /**
     * Activity entries are never edited, so there's no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
            'properties' => 'array',
        ];
    }

    /**
     * Get the request the activity belongs to.
     *
     * @return BelongsTo<JobRequest, $this>
     */
    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    /**
     * Get the user who performed the activity. Null for guests and system actions.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
