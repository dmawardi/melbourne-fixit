<?php

namespace App\Models;

use App\Enums\VendorDocument;
use App\Models\Concerns\StoredPrivately;
use Carbon\CarbonImmutable;
use Database\Factories\VendorFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A copy of a vendor's licence, insurance certificate or other document. Only admins can see these.
 *
 * @property int $id
 * @property int $vendor_id
 * @property int|null $uploaded_by
 * @property VendorDocument|null $document
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property CarbonImmutable|null $uploaded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'document',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'uploaded_at',
])]
#[Hidden(['disk', 'path'])]
class VendorFile extends Model
{
    /** @use HasFactory<VendorFileFactory> */
    use HasFactory, StoredPrivately;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document' => VendorDocument::class,
            'size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * Get the vendor the file belongs to.
     *
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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
}
