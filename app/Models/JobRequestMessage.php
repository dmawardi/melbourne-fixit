<?php

namespace App\Models;

use App\Enums\Visibility;
use Carbon\CarbonImmutable;
use Database\Factories\JobRequestMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $job_request_id
 * @property int|null $user_id
 * @property string $body
 * @property Visibility $visibility
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['body', 'visibility'])]
class JobRequestMessage extends Model
{
    /** @use HasFactory<JobRequestMessageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => Visibility::class,
        ];
    }

    /**
     * Get the request the message belongs to.
     *
     * @return BelongsTo<JobRequest, $this>
     */
    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    /**
     * Get the user who posted the message.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to messages the customer is allowed to see.
     *
     * @param  Builder<JobRequestMessage>  $query
     */
    #[Scope]
    protected function shared(Builder $query): void
    {
        $query->where('visibility', Visibility::Customer);
    }

    /**
     * Get a short excerpt of the message for the activity log.
     */
    public function excerpt(): string
    {
        return Str::limit($this->body, 100);
    }
}
