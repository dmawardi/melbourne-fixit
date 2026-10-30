<?php

namespace App\Http\Requests\JobRequests;

use App\Concerns\JobRequestValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequestRequest extends FormRequest
{
    use JobRequestValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->jobRequestRules(),
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            // Honeypot: hidden from real users, so any value means a bot filled in the form.
            'website' => ['prohibited'],
        ];
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
