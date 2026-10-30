<?php

namespace App\Models;

use App\Enums\BudgetRange;
use App\Enums\JobCategory;
use App\Enums\JobRequestStatus;
use App\Enums\JobUrgency;
use App\Enums\PropertyType;
use Carbon\CarbonImmutable;
use Database\Factories\JobRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property CarbonImmutable|null $claimed_at
 * @property string $contact_name
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property string $suburb
 * @property string $postcode
 * @property string|null $address_line
 * @property JobCategory $category
 * @property string $title
 * @property string $description
 * @property JobUrgency $urgency
 * @property BudgetRange|null $budget_range
 * @property PropertyType|null $property_type
 * @property JobRequestStatus $status
 * @property string|null $submitted_ip
 * @property string|null $submitted_user_agent
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'contact_name',
    'contact_email',
    'contact_phone',
    'suburb',
    'postcode',
    'address_line',
    'category',
    'title',
    'description',
    'urgency',
    'budget_range',
    'property_type',
])]
#[Hidden(['id', 'user_id', 'submitted_ip', 'submitted_user_agent'])]
#[RouteKey('public_id')]
class JobRequest extends Model
{
    /** @use HasFactory<JobRequestFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The maximum number of photos a single request may have.
     */
    public const MAX_PHOTOS = 10;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'category' => JobCategory::class,
            'urgency' => JobUrgency::class,
            'budget_range' => BudgetRange::class,
            'property_type' => PropertyType::class,
            'status' => JobRequestStatus::class,
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        // Photo files aren't removed by the database cascade, so clean them up explicitly.
        static::forceDeleting(function (JobRequest $jobRequest): void {
            $jobRequest->photos()->get()->each->delete();
        });
    }

    /**
     * Normalise the contact email so it can be matched against user accounts.
     *
     * @return Attribute<never, string>
     */
    protected function contactEmail(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower(trim($value)),
        );
    }

    /**
     * Get the user who owns the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the photos attached to the request.
     *
     * @return HasMany<JobRequestPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(JobRequestPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope a query to requests not yet linked to a user account.
     *
     * @param  Builder<JobRequest>  $query
     */
    #[Scope]
    protected function unclaimed(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    /**
     * Link the request to the given user.
     */
    public function assignTo(User $user): void
    {
        $this->forceFill([
            'user_id' => $user->id,
            'claimed_at' => now(),
        ])->save();
    }

    /**
     * Get the number of photos that can still be added to the request.
     */
    public function remainingPhotoSlots(): int
    {
        return max(0, self::MAX_PHOTOS - $this->photos()->count());
    }
}
