<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobCategory;
use App\Enums\VendorDocument;
use App\Enums\VendorDocumentStatus;
use App\Enums\VendorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Http\Resources\Admin\VendorFileResource;
use App\Http\Resources\Admin\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    /**
     * The number of vendors shown on each page.
     */
    private const PER_PAGE = 25;

    /**
     * Show all vendors, excluding archived ones, with those needing attention first.
     *
     * The status is worked out in PHP, so the filtering and paging happen here too.
     */
    public function index(Request $request): Response
    {
        $status = VendorDocumentStatus::tryFrom($request->string('documents')->value());

        $vendors = Vendor::query()
            ->withCount('jobRequests')
            ->orderBy('name')
            ->get()
            ->sortBy(fn (Vendor $vendor): int => $vendor->documentStatus()->priority())
            ->values();

        $matching = $status
            ? $vendors->filter(fn (Vendor $vendor): bool => $vendor->documentStatus() === $status)->values()
            : $vendors;

        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginated = new LengthAwarePaginator(
            $matching->forPage($page, self::PER_PAGE)->values(),
            $matching->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('admin/vendors/index', [
            'vendors' => VendorResource::collection($paginated),
            'filters' => ['documents' => $status?->value],
            'counts' => [
                'all' => $vendors->count(),
                'action_needed' => $vendors->filter(fn (Vendor $vendor): bool => $vendor->documentStatus() === VendorDocumentStatus::ActionNeeded)->count(),
                'expiring_soon' => $vendors->filter(fn (Vendor $vendor): bool => $vendor->documentStatus() === VendorDocumentStatus::ExpiringSoon)->count(),
            ],
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
        $vendor->load('files.uploader');

        return Inertia::render('admin/vendors/edit', [
            'vendor' => (new VendorResource($vendor))->resolve($request),
            'options' => $this->options(),
            'files' => VendorFileResource::collection($vendor->files)->resolve($request),
            // The documents to collect for the vendor's job types, as saved rather than as edited in the form.
            'fileDocuments' => array_map(fn (VendorDocument $document): array => $document->toOption(), $vendor->applicableDocuments()),
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
