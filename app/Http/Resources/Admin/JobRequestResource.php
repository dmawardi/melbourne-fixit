<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\JobRequestResource as CustomerJobRequestResource;
use App\Models\JobRequest;
use Illuminate\Http\Request;

/**
 * The admin view of a job request, including internal notes, files, messages and the activity log.
 *
 * @mixin JobRequest
 */
class JobRequestResource extends CustomerJobRequestResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'admin_notes' => $this->admin_notes,
            'owner' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'claimed_at' => $this->claimed_at?->toIso8601String(),
            'submitted_ip' => $this->submitted_ip,
            'messages_count' => $this->whenCounted('messages'),
            'files' => JobRequestFileResource::collection($this->whenLoaded('files')),
            'messages' => JobRequestMessageResource::collection($this->whenLoaded('messages')),
            'activities' => JobRequestActivityResource::collection($this->whenLoaded('activities')),
            'latest_activity' => $this->whenLoaded('latestActivity', fn () => $this->latestActivity
                ? new JobRequestActivityResource($this->latestActivity)
                : null),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
