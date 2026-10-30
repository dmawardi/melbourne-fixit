<?php

namespace App\Http\Resources;

use App\Models\JobRequestFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobRequestFile
 */
class JobRequestFileResource extends JsonResource
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
            'url' => $this->temporaryUrl(),
            'kind' => $this->kind->toOption(),
            'is_image' => $this->isImage(),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
