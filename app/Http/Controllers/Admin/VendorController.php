<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobCategory;
use App\Enums\VendorDocument;
use App\Enums\VendorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Http\Resources\Admin\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    /**
     * Show all vendors, excluding archived ones.
     */
    public function index(): Response
    {
        $vendors = Vendor::query()
            ->withCount('jobRequests')
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('admin/vendors/index', [
            'vendors' => VendorResource::collection($vendors),
        ]);
    }

    /**
     * Show the form for adding a vendor.
     */
    public function create(): Response
    {
        return Inertia::render('admin/vendors/create', [
            'options' => $this->options(),
        ]);
    }

    /**
     * Add a vendor.
     */
    public function store(StoreVendorRequest $request): RedirectResponse
    {
        Vendor::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor added.')]);

        return to_route('admin.vendors.index');
    }

    /**
     * Show the form for editing a vendor.
     */
    public function edit(Request $request, Vendor $vendor): Response
    {
        return Inertia::render('admin/vendors/edit', [
            'vendor' => (new VendorResource($vendor))->resolve($request),
            'options' => $this->options(),
        ]);
    }

    /**
     * Update a vendor.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor updated.')]);

        return to_route('admin.vendors.index');
    }

    /**
     * Archive a vendor. Requests already assigned to the vendor keep it.
     */
    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor archived.')]);

        return to_route('admin.vendors.index');
    }

    /**
     * Get the select options for the vendor form.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function options(): array
    {
        return [
            'types' => VendorType::options(),
            'categories' => JobCategory::options(),
            'documents' => VendorDocument::requirements(),
        ];
    }
}
