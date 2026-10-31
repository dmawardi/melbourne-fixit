<?php

namespace App\Models;

use App\Enums\JobCategory;
use App\Enums\VendorDocument;
use App\Enums\VendorDocumentStatus;
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
     * How many days before a document expires to start flagging it.
     */
    public const EXPIRY_WARNING_DAYS = 30;

    /**
     * How many months the documents can go between checks.
     */
    public const CHECK_INTERVAL_MONTHS = 12;

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
     * Get the documents that apply to the vendor's job types.
     *
     * @return list<VendorDocument>
     */
    public function applicableDocuments(): array
    {
        return array_values(array_filter(
            VendorDocument::cases(),
            fn (VendorDocument $document): bool => $document->categories() === null
                || collect($document->categories())->contains(fn (JobCategory $category): bool => $this->canHandle($category)),
        ));
    }

    /**
     * Determine whether the vendor certainly needs the document. Other documents depend on the job
     * or on how the vendor operates, so they can't be flagged just for being empty.
     */
    private function requiresDocument(VendorDocument $document): bool
    {
        return match ($document) {
            VendorDocument::Business, VendorDocument::PublicLiability => true,
            VendorDocument::PlumbingLicence => $this->categories?->contains(JobCategory::Plumbing) ?? false,
            VendorDocument::ElectricalRegistration => $this->categories?->contains(JobCategory::Electrical) ?? false,
            default => false,
        };
    }

    /**
     * Get the problems with the vendor's documents, most serious first.
     *
     * @return list<array{message: string, status: VendorDocumentStatus}>
     */
    public function documentIssues(): array
    {
        $today = today(config('business.timezone'));
        $issues = [];

        foreach ($this->applicableDocuments() as $document) {
            $expiryColumn = $document->expiryColumn();
            $expiresOn = $expiryColumn ? $this->{$expiryColumn} : null;

            if ($this->requiresDocument($document) && (! $this->{$document->numberColumn()} || ($expiryColumn && ! $expiresOn))) {
                $issues[] = [
                    'message' => ($document === VendorDocument::Business ? 'ABN' : $document->label()).' missing',
                    'status' => VendorDocumentStatus::ActionNeeded,
                ];
            } elseif ($expiresOn && $expiresOn->toDateString() < $today->toDateString()) {
                $issues[] = [
                    'message' => $document->label().' expired '.$expiresOn->format('j M Y'),
                    'status' => VendorDocumentStatus::ActionNeeded,
                ];
            } elseif ($expiresOn && $expiresOn->toDateString() <= $today->copy()->addDays(self::EXPIRY_WARNING_DAYS)->toDateString()) {
                $issues[] = [
                    'message' => $document->label().' expires '.$expiresOn->format('j M Y'),
                    'status' => VendorDocumentStatus::ExpiringSoon,
                ];
            }
        }

        if (! $this->documents_checked_on) {
            $issues[] = ['message' => 'Documents never checked', 'status' => VendorDocumentStatus::ActionNeeded];
        } elseif ($this->documents_checked_on->toDateString() < $today->copy()->subMonths(self::CHECK_INTERVAL_MONTHS)->toDateString()) {
            $issues[] = [
                'message' => 'Documents last checked '.$this->documents_checked_on->format('j M Y'),
                'status' => VendorDocumentStatus::ActionNeeded,
            ];
        }

        usort($issues, fn (array $a, array $b): int => $a['status']->priority() <=> $b['status']->priority());

        return $issues;
    }

    /**
     * Get the overall status of the vendor's documents.
     */
    public function documentStatus(): VendorDocumentStatus
    {
        return $this->documentIssues()[0]['status'] ?? VendorDocumentStatus::UpToDate;
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
