<?php

namespace App\Http\Requests\Admin;

use App\Enums\FileKind;
use App\Enums\Visibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequestFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:20480'],
            'kind' => ['required', Rule::enum(FileKind::class)],
            'visibility' => ['required', Rule::enum(Visibility::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.max' => __('You can upload at most :max files at a time.'),
            'files.*.max' => __('Each file must be 20MB or smaller.'),
            'files.*.mimes' => __('Files must be images, PDFs, Word documents or Excel spreadsheets.'),
        ];
    }
}
