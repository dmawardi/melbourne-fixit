<?php

namespace App\Concerns;

use App\Enums\BudgetRange;
use App\Enums\JobCategory;
use App\Enums\JobUrgency;
use App\Enums\PropertyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait JobRequestValidationRules
{
    /**
     * Get the validation rules for the job request details a customer can edit.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function jobRequestRules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\s-]{8,}$/'],
            'suburb' => ['required', 'string', 'max:100'],
            'postcode' => $this->postcodeRules(),
            'address_line' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(JobCategory::class)],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'urgency' => ['required', Rule::enum(JobUrgency::class)],
            'budget_range' => ['nullable', Rule::enum(BudgetRange::class)],
            'property_type' => ['nullable', Rule::enum(PropertyType::class)],
        ];
    }

    /**
     * Get the validation rules for postcodes. Only Victorian postcodes are serviced.
     *
     * @return array<int, string>
     */
    protected function postcodeRules(): array
    {
        return ['required', 'string', 'regex:/^3\d{3}$/'];
    }

    /**
     * Get the custom validation messages for job requests.
     *
     * @return array<string, string>
     */
    protected function jobRequestMessages(): array
    {
        return [
            'postcode.regex' => __('We currently only service Victorian postcodes.'),
            'contact_phone.regex' => __('Please enter a valid phone number.'),
        ];
    }
}
