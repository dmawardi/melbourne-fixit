<?php

namespace App\Http\Resources;

use App\Models\JobRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The customer's view of a job request. Must never include admin notes, the activity
 * log or admin-only files and messages.
 *
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
            'category' => $this->category->toOption(),
            'urgency' => $this->urgency->toOption(),
            'open_emergency' => $this->isOpenEmergency(),
            'budget_range' => $this->budget_range?->toOption(),
            'property_type' => $this->property_type?->toOption(),
            'status' => $this->status->toOption(),
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'suburb' => $this->suburb,
            'postcode' => $this->postcode,
            'address_line' => $this->address_line,
            'files_count' => $this->whenCounted('files'),
            'files' => JobRequestFileResource::collection($this->whenLoaded('files')),
            'messages' => JobRequestMessageResource::collection($this->whenLoaded('messages')),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
