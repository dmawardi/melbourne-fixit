<?php

namespace App\Http\Resources;

use App\Models\JobRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobRequest
 */
class JobRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'urgency' => ['value' => $this->urgency->value, 'label' => $this->urgency->label()],
            'budget_range' => $this->budget_range
                ? ['value' => $this->budget_range->value, 'label' => $this->budget_range->label()]
                : null,
            'property_type' => $this->property_type
                ? ['value' => $this->property_type->value, 'label' => $this->property_type->label()]
                : null,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'suburb' => $this->suburb,
            'postcode' => $this->postcode,
            'address_line' => $this->address_line,
            'photos_count' => $this->whenCounted('photos'),
            'photos' => JobRequestPhotoResource::collection($this->whenLoaded('photos')),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
