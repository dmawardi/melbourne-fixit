<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\JobRequestFileResource as CustomerJobRequestFileResource;
use App\Models\JobRequestFile;
use Illuminate\Http\Request;

/**
 * @mixin JobRequestFile
 */
class JobRequestFileResource extends CustomerJobRequestFileResource
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
            'visibility' => $this->visibility->toOption(),
            'uploader' => $this->whenLoaded('uploader', fn () => $this->uploader ? [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
                'is_admin' => $this->uploader->isAdmin(),
            ] : null),
        ];
    }
}
