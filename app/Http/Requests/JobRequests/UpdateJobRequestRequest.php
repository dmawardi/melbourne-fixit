<?php

namespace App\Http\Requests\JobRequests;

use App\Concerns\JobRequestValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequestRequest extends FormRequest
{
    use JobRequestValidationRules;

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
     * The contact email is deliberately not editable, since it's what links the request to an account.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_map(
            fn (array $rules): array => ['sometimes', ...$rules],
            $this->jobRequestRules(),
        );
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->jobRequestMessages();
    }
}
