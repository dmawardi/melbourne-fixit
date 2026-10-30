<?php

namespace App\Http\Requests\JobRequests;

use App\Enums\Visibility;
use App\Models\JobRequestFile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequestPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var JobRequestFile $file */
        $file = $this->route('file');

        // Admin-only files must not be revealed, even by a different error code.
        abort_unless($file->visibility === Visibility::Customer, 404);

        return $this->user()->can('update', $file);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'caption' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
