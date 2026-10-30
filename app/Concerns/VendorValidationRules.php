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
        ];
    }
}
