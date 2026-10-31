<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use App\Enums\BudgetRange;
use App\Enums\JobCategory;
use App\Enums\JobRequestStatus;
use App\Enums\JobUrgency;
use App\Enums\PropertyType;
use App\Models\Concerns\TracksChanges;
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
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property int|null $vendor_id
 * @property string|null $admin_notes
 * @property CarbonImmutable|null $last_activity_at
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
    'status',
    'admin_notes',
])]
#[Hidden(['id', 'user_id', 'vendor_id', 'admin_notes', 'submitted_ip', 'submitted_user_agent'])]
#[RouteKey('public_id')]
class JobRequest extends Model
{
    /** @use HasFactory<JobRequestFactory> */
    use HasFactory, HasUlids, SoftDeletes, TracksChanges;

    /**
     * The maximum number of photos a customer may add to a single request.
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
            'last_activity_at' => 'datetime',
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
        // Stored files aren't removed by the database cascade, so clean them up explicitly.
        static::forceDeleting(function (JobRequest $jobRequest): void {
            $jobRequest->files()->get()->each->delete();
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
     * Get the vendor assigned to the request, even if the vendor has since been archived.
     *
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    /**
     * Get the files attached to the request, including admin-only files.
     *
     * @return HasMany<JobRequestFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(JobRequestFile::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the messages posted on the request, including admin-only messages.
     *
     * @return HasMany<JobRequestMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(JobRequestMessage::class)->latest()->latest('id');
    }

    /**
     * Get the activity log for the request, newest first.
     *
     * @return HasMany<JobRequestActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(JobRequestActivity::class)->latest()->latest('id');
    }

    /**
     * Get the most recent activity on the request.
     *
     * @return HasOne<JobRequestActivity, $this>
     */
    public function latestActivity(): HasOne
    {
        return $this->hasOne(JobRequestActivity::class)->latestOfMany();
    }

    /**
     * Scope a query to new requests no one has reviewed yet.
     *
     * @param  Builder<JobRequest>  $query
     */
    #[Scope]
    protected function awaitingReview(Builder $query): void
    {
        $query->where('status', JobRequestStatus::Submitted);
    }

    /**
     * Scope a query to requests past the new stage that are still open and have no vendor.
     *
     * @param  Builder<JobRequest>  $query
     */
    #[Scope]
    protected function needsVendor(Builder $query): void
    {
        $query->whereNull('vendor_id')->whereIn('status', [
            JobRequestStatus::Reviewing,
            JobRequestStatus::Quoted,
            JobRequestStatus::Scheduled,
        ]);
    }

    /**
     * Order a query with emergencies first.
     *
     * @param  Builder<JobRequest>  $query
     */
    #[Scope]
    protected function urgentFirst(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN urgency = ? THEN 0 ELSE 1 END', [JobUrgency::Emergency->value]);
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

        $this->recordActivity(ActivityEvent::Claimed, by: $user);
    }

    /**
     * Assign a vendor to the request, replacing any vendor already assigned.
     */
    public function assignVendor(Vendor $vendor, ?User $by = null): void
    {
        if ($this->vendor_id === $vendor->id) {
            return;
        }

        $this->removeVendor($by);

        $this->vendor()->associate($vendor)->save();

        $this->recordActivity(ActivityEvent::VendorAssigned, [
            ...$vendor->activityProperties(),
            'outside_categories' => ! $vendor->canHandle($this->category),
        ], $by);
    }

    /**
     * Remove the vendor assigned to the request, if there is one.
     */
    public function removeVendor(?User $by = null): void
    {
        $vendor = $this->vendor;

        if ($vendor === null) {
            return;
        }

        $this->vendor()->dissociate()->save();

        $this->recordActivity(ActivityEvent::VendorUnassigned, $vendor->activityProperties(), $by);
    }

    /**
     * Get the number of photos the customer can still add to the request.
     *
     * Files uploaded by admins don't count towards the customer's limit.
     */
    public function remainingPhotoSlots(): int
    {
        $customerUploads = $this->user_id === null
            ? 0
            : $this->files()->where('uploaded_by', $this->user_id)->count();

        return max(0, self::MAX_PHOTOS - $customerUploads);
    }

    /**
     * Add an entry to the request's activity log and mark the request as recently active.
     *
     * @param  array<string, mixed>  $properties
     */
    public function recordActivity(ActivityEvent $event, array $properties = [], ?User $by = null): JobRequestActivity
    {
        $by ??= auth()->user();

        $activity = new JobRequestActivity([
            'event' => $event,
            'properties' => $properties ?: null,
        ]);
        $activity->user_id = $by instanceof User ? $by->id : null;
        $this->activities()->save($activity);

        // Update directly so this doesn't touch updated_at or fire model events. Includes
        // trashed rows so deletions are recorded too.
        $this->last_activity_at = $activity->created_at;
        $this->newQueryWithoutScopes()->whereKey($this->getKey())->update(['last_activity_at' => $this->last_activity_at]);
        $this->syncOriginalAttribute('last_activity_at');

        return $activity;
    }

    /**
     * Update the request and record what changed in the activity log.
     *
     * Status and admin notes get their own log entries; everything else is grouped into one.
     *
     * @param  array<string, mixed>  $attributes  Validated attributes.
     */
    public function updateWithActivity(array $attributes, ?User $by = null): void
    {
        $this->fill($attributes);
        $changes = $this->pendingChanges();

        if ($changes === []) {
            return;
        }

        $this->save();

        if (isset($changes['status'])) {
            $this->recordActivity(ActivityEvent::StatusChanged, $changes['status'], $by);
        }

        if (isset($changes['admin_notes'])) {
            $this->recordActivity(ActivityEvent::NotesUpdated, $changes['admin_notes'], $by);
        }

        $otherChanges = array_diff_key($changes, array_flip(['status', 'admin_notes']));

        if ($otherChanges !== []) {
            $this->recordActivity(ActivityEvent::Updated, ['changes' => $otherChanges], $by);
        }
    }
}
