<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\JobRequestMessageResource as CustomerJobRequestMessageResource;
use App\Models\JobRequestMessage;
use Illuminate\Http\Request;

/**
 * @mixin JobRequestMessage
 */
class JobRequestMessageResource extends CustomerJobRequestMessageResource
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
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null),
        ];
    }
}
