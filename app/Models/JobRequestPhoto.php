<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\JobRequestPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $job_request_id
 * @property int|null $uploaded_by
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
class JobRequestPhoto extends Model
{
    /** @use HasFactory<JobRequestPhotoFactory> */
    use HasFactory;

    /**
     * How long signed photo URLs remain valid, in minutes.
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
        static::deleted(function (JobRequestPhoto $photo): void {
            Storage::disk($photo->disk)->delete($photo->path);
        });
    }

    /**
     * Get the request the photo belongs to.
     *
     * @return BelongsTo<JobRequest, $this>
     */
    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    /**
     * Get the user who uploaded the photo.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get a short-lived signed URL for viewing the photo.
     *
     * Photos are stored privately, so every URL is signed and expires.
     */
    public function temporaryUrl(): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $this->path,
            now()->addMinutes(self::URL_TTL_MINUTES),
        );
    }
}
