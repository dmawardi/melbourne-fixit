<?php

namespace App\Http\Requests\JobRequests;

use App\Models\JobRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequestPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('jobRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var JobRequest $jobRequest */
        $jobRequest = $this->route('jobRequest');

        return [
            'photos' => ['required', 'array', 'min:1', 'max:'.$jobRequest->remainingPhotoSlots()],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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
            'photos.max' => __('A request can have at most :max photos.', ['max' => JobRequest::MAX_PHOTOS]),
            'photos.*.max' => __('Each photo must be 10MB or smaller.'),
        ];
    }
}
