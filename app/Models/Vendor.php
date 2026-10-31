<?php

namespace App\Models;

use App\Enums\JobCategory;
use App\Enums\VendorType;
use Carbon\CarbonImmutable;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property VendorType $type
 * @property Collection<int, JobCategory>|null $categories
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $notes
 * @property string|null $abn
 * @property string|null $public_liability_insurer
 * @property string|null $public_liability_policy_number
 * @property int|null $public_liability_cover
 * @property CarbonImmutable|null $public_liability_expires_on
 * @property string|null $workcover_policy_number
 * @property CarbonImmutable|null $workcover_expires_on
 * @property string|null $builder_registration_number
 * @property CarbonImmutable|null $builder_registration_expires_on
 * @property string|null $plumbing_licence_number
 * @property CarbonImmutable|null $plumbing_licence_expires_on
 * @property string|null $electrical_registration_number
 * @property CarbonImmutable|null $electrical_registration_expires_on
 * @property CarbonImmutable|null $documents_checked_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'name',
    'type',
    'categories',
    'contact_name',
    'email',
    'phone',
    'notes',
    'abn',
    'public_liability_insurer',
    'public_liability_policy_number',
    'public_liability_cover',
    'public_liability_expires_on',
    'workcover_policy_number',
    'workcover_expires_on',
    'builder_registration_number',
    'builder_registration_expires_on',
    'plumbing_licence_number',
    'plumbing_licence_expires_on',
    'electrical_registration_number',
    'electrical_registration_expires_on',
    'documents_checked_on',
])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => VendorType::class,
            'categories' => AsEnumCollection::of(JobCategory::class),
            'public_liability_cover' => 'integer',
            'public_liability_expires_on' => 'immutable_date',
            'workcover_expires_on' => 'immutable_date',
            'builder_registration_expires_on' => 'immutable_date',
            'plumbing_licence_expires_on' => 'immutable_date',
            'electrical_registration_expires_on' => 'immutable_date',
            'documents_checked_on' => 'immutable_date',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        // General vendors cover everything, so a stored list would only ever go stale.
        static::saving(function (Vendor $vendor): void {
            if ($vendor->type === VendorType::General) {
                $vendor->categories = null;
            }
        });
    }

    /**
     * Get the job requests assigned to the vendor.
     *
     * @return HasMany<JobRequest, $this>
     */
    public function jobRequests(): HasMany
    {
        return $this->hasMany(JobRequest::class);
    }

    /**
     * Determine whether the vendor takes jobs in the given category.
     */
    public function canHandle(JobCategory $category): bool
    {
        return $this->type === VendorType::General
            || ($this->categories?->contains($category) ?? false);
    }

    /**
     * Get the details to store in the activity log, so entries stay readable if the vendor changes.
     *
     * @return array<string, mixed>
     */
    public function activityProperties(): array
    {
        return [
            'vendor_id' => $this->id,
            'vendor_name' => $this->name,
            'vendor_type' => $this->type->value,
        ];
    }
}
