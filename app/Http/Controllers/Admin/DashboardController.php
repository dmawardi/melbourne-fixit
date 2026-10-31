<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorDocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\JobRequestResource;
use App\Http\Resources\Admin\VendorResource;
use App\Models\JobRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The number of items listed in each section.
     */
    private const SECTION_LIMIT = 8;

    /**
     * Show what needs an admin's attention.
     */
    public function __invoke(Request $request): Response
    {
        $vendors = Vendor::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (Vendor $vendor): bool => $vendor->documentStatus() !== VendorDocumentStatus::UpToDate)
            ->sortBy(fn (Vendor $vendor): int => $vendor->documentStatus()->priority())
            ->values();

        return Inertia::render('admin/dashboard', [
            'newRequests' => [
                'total' => JobRequest::query()->awaitingReview()->count(),
                'items' => JobRequestResource::collection(
                    JobRequest::query()->awaitingReview()->urgentFirst()->oldest()->limit(self::SECTION_LIMIT)->get()
                )->resolve($request),
            ],
            'unassignedRequests' => [
                'total' => JobRequest::query()->needsVendor()->count(),
                'items' => JobRequestResource::collection(
                    JobRequest::query()->needsVendor()->urgentFirst()->oldest()->limit(self::SECTION_LIMIT)->get()
                )->resolve($request),
            ],
            'vendorDocuments' => [
                'action_needed' => $vendors->filter(fn (Vendor $vendor): bool => $vendor->documentStatus() === VendorDocumentStatus::ActionNeeded)->count(),
                'expiring_soon' => $vendors->filter(fn (Vendor $vendor): bool => $vendor->documentStatus() === VendorDocumentStatus::ExpiringSoon)->count(),
                'items' => VendorResource::collection($vendors->take(self::SECTION_LIMIT))->resolve($request),
            ],
        ]);
    }
}
