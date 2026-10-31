<?php

namespace App\Http\Resources\Admin;

use App\Enums\JobCategory;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vendor
 */
class VendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->toOption(),
            'categories' => $this->categories?->map(fn (JobCategory $category): array => $category->toOption())->values() ?? [],
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'abn' => $this->abn,
            'public_liability_insurer' => $this->public_liability_insurer,
            'public_liability_policy_number' => $this->public_liability_policy_number,
            'public_liability_cover' => $this->public_liability_cover,
            'public_liability_expires_on' => $this->public_liability_expires_on?->toDateString(),
            'workcover_policy_number' => $this->workcover_policy_number,
            'workcover_expires_on' => $this->workcover_expires_on?->toDateString(),
            'builder_registration_number' => $this->builder_registration_number,
            'builder_registration_expires_on' => $this->builder_registration_expires_on?->toDateString(),
            'plumbing_licence_number' => $this->plumbing_licence_number,
            'plumbing_licence_expires_on' => $this->plumbing_licence_expires_on?->toDateString(),
            'electrical_registration_number' => $this->electrical_registration_number,
            'electrical_registration_expires_on' => $this->electrical_registration_expires_on?->toDateString(),
            'documents_checked_on' => $this->documents_checked_on?->toDateString(),
            'job_requests_count' => $this->whenCounted('jobRequests'),
            'archived' => $this->trashed(),
        ];
    }
}
