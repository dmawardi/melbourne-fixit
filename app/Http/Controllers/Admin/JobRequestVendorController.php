<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignJobRequestVendorRequest;
use App\Models\JobRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobRequestVendorController extends Controller
{
    /**
     * Assign a vendor to a job request, replacing any vendor already assigned.
     */
    public function update(AssignJobRequestVendorRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $jobRequest->assignVendor(Vendor::findOrFail($request->integer('vendor_id')), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor assigned.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }

    /**
     * Remove the vendor from a job request.
     */
    public function destroy(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $jobRequest->removeVendor($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vendor removed.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }
}
