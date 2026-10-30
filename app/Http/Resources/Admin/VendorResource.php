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
            'job_requests_count' => $this->whenCounted('jobRequests'),
            'archived' => $this->trashed(),
        ];
    }
}
