<?php

namespace App\Concerns;

use App\Enums\JobCategory;
use App\Enums\VendorType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait VendorValidationRules
{
    /**
     * Get the validation rules for a vendor's details.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function vendorRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(VendorType::class)],
            'categories' => ['exclude_if:type,'.VendorType::General->value, 'required', 'array', 'min:1'],
            'categories.*' => ['exclude_if:type,'.VendorType::General->value, 'distinct', Rule::enum(JobCategory::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:20000'],
            'abn' => ['nullable', 'regex:/^\d{2} ?\d{3} ?\d{3} ?\d{3}$/'],
            'public_liability_insurer' => ['nullable', 'string', 'max:255'],
            'public_liability_policy_number' => ['nullable', 'string', 'max:64'],
            // Electrical contractors must hold at least $5 million in cover.
            'public_liability_cover' => [
                $this->filled('electrical_registration_number') ? 'required' : 'nullable',
                'integer',
                'min:'.($this->filled('electrical_registration_number') ? 5_000_000 : 0),
            ],
            'public_liability_expires_on' => ['nullable', 'date', 'required_with:public_liability_policy_number'],
            'workcover_policy_number' => ['nullable', 'string', 'max:64'],
            'workcover_expires_on' => ['nullable', 'date', 'required_with:workcover_policy_number'],
            'builder_registration_number' => ['nullable', 'string', 'max:64'],
            'builder_registration_expires_on' => ['nullable', 'date', 'required_with:builder_registration_number'],
            'plumbing_licence_number' => ['nullable', 'string', 'max:64'],
            'plumbing_licence_expires_on' => ['nullable', 'date', 'required_with:plumbing_licence_number'],
            'electrical_registration_number' => ['nullable', 'string', 'max:64'],
            'electrical_registration_expires_on' => ['nullable', 'date', 'required_with:electrical_registration_number'],
            'documents_checked_on' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    protected function vendorMessages(): array
    {
        return [
            'categories.required' => __('Choose at least one type of job for a specialist.'),
            'categories.min' => __('Choose at least one type of job for a specialist.'),
            'abn.regex' => __('Enter an 11-digit ABN.'),
            'public_liability_cover.required' => __('Enter the cover amount. Electrical contractors must hold at least $5 million.'),
            'public_liability_cover.min' => __('Electrical contractors must hold at least $5 million in cover.'),
            '*.required_with' => __('Enter the expiry date.'),
            'documents_checked_on.before_or_equal' => __('This date can\'t be in the future.'),
        ];
    }
}
