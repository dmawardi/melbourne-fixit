<?php

namespace App\Http\Resources\Admin;

use App\Models\VendorFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VendorFile
 */
class VendorFileResource extends JsonResource
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
            'document' => $this->document?->value,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'uploader' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
