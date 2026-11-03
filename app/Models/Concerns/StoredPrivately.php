<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * For models that point at a private file on a disk. The file is served through short-lived
 * signed URLs and deleted from the disk along with the model.
 *
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime_type
 */
trait StoredPrivately
{
    /**
     * How long signed file URLs remain valid, in minutes.
     */
    public const URL_TTL_MINUTES = 30;

    /**
     * Boot the trait.
     */
    protected static function bootStoredPrivately(): void
    {
        static::deleted(function (self $model): void {
            Storage::disk($model->disk)->delete($model->path);
        });
    }

    /**
     * Determine if the file is an image that can be shown inline.
     */
    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
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
