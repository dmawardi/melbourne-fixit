<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\Visibility;
use App\Models\Concerns\TracksChanges;
use Carbon\CarbonImmutable;
use Database\Factories\JobRequestFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $job_request_id
 * @property int|null $uploaded_by
 * @property FileKind $kind
 * @property Visibility $visibility
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $caption
 * @property int $sort_order
 * @property CarbonImmutable|null $uploaded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'kind',
    'visibility',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'width',
    'height',
    'caption',
    'sort_order',
    'uploaded_at',
])]
#[Hidden(['disk', 'path'])]
class JobRequestFile extends Model
{
    /** @use HasFactory<JobRequestFileFactory> */
    use HasFactory, TracksChanges;

    /**
     * How long signed file URLs remain valid, in minutes.
     */
    public const URL_TTL_MINUTES = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => FileKind::class,
            'visibility' => Visibility::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::deleted(function (JobRequestFile $file): void {
            Storage::disk($file->disk)->delete($file->path);
        });
    }

    /**
     * Get the request the file belongs to.
     *
     * @return BelongsTo<JobRequest, $this>
     */
    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    /**
     * Get the user who uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to files the customer is allowed to see.
     *
     * @param  Builder<JobRequestFile>  $query
     */
    #[Scope]
    protected function shared(Builder $query): void
    {
        $query->where('visibility', Visibility::Customer);
    }

    /**
     * Determine if the file is an image that can be shown inline.
     */
    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * Describe the file for the activity log.
     *
     * @return array<string, mixed>
     */
    public function activityProperties(): array
    {
        return [
            'file_id' => $this->id,
            'name' => $this->original_name,
            'kind' => $this->kind->value,
            'visibility' => $this->visibility->value,
        ];
    }

    /**
     * Update the file and log what changed on its job request.
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

        $this->jobRequest->recordActivity(
            ActivityEvent::FileUpdated,
            [...$this->activityProperties(), 'changes' => $changes],
            $by,
        );
    }

    /**
     * Get a short-lived signed URL for the file.
     *
     * Files are stored privately, so every URL is signed and expires. Non-images are
     * served as downloads under their original filename.
     */
    public function temporaryUrl(): string
    {
        $options = [];

        if (! $this->isImage() && $this->original_name !== null) {
            $filename = str_replace(['"', '\\'], '', $this->original_name);
            $options['ResponseContentDisposition'] = 'attachment; filename="'.$filename.'"';
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $this->path,
            now()->addMinutes(self::URL_TTL_MINUTES),
            $options,
        );
    }
}
