<?php

namespace App\Actions\Vendors;

use App\Enums\VendorDocument;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreVendorFiles
{
    /**
     * Store uploaded copies of a vendor's documents privately.
     *
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, VendorFile>
     */
    public function handle(Vendor $vendor, array $files, ?VendorDocument $document, User $uploader): Collection
    {
        $disk = config('filesystems.vendor_documents');
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($vendor, $files, $document, $uploader, $disk, &$storedPaths): Collection {
                $stored = new Collection;

                foreach ($files as $file) {
                    $path = $file->storeAs(
                        "vendors/{$vendor->id}",
                        Str::lower((string) Str::ulid()).'.'.$file->extension(),
                        ['disk' => $disk, 'visibility' => 'private'],
                    );

                    if ($path === false) {
                        throw new RuntimeException('Unable to store vendor file.');
                    }

                    $storedPaths[] = $path;

                    $vendorFile = new VendorFile([
                        'document' => $document,
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now(),
                    ]);
                    $vendorFile->uploaded_by = $uploader->id;

                    $vendor->files()->save($vendorFile);
                    $stored->push($vendorFile);
                }

                return $stored;
            });
        } catch (Throwable $e) {
            // Don't leave orphaned files behind if any part of the upload fails.
            Storage::disk($disk)->delete($storedPaths);

            throw $e;
        }
    }
}
