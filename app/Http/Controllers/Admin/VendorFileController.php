<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Vendors\StoreVendorFiles;
use App\Enums\VendorDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorFileRequest;
use App\Models\Vendor;
use App\Models\VendorFile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class VendorFileController extends Controller
{
    /**
     * Upload copies of a vendor's documents.
     */
    public function store(StoreVendorFileRequest $request, Vendor $vendor, StoreVendorFiles $storeVendorFiles): RedirectResponse
    {
        $storeVendorFiles->handle(
            $vendor,
            $request->file('files'),
            $request->enum('document', VendorDocument::class),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Files uploaded.')]);

        return to_route('admin.vendors.edit', $vendor);
    }

    /**
     * Delete a file from a vendor.
     */
    public function destroy(Vendor $vendor, VendorFile $file): RedirectResponse
    {
        $file->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File deleted.')]);

        return to_route('admin.vendors.edit', $vendor);
    }
}
