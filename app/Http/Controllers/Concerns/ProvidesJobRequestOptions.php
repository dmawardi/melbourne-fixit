<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\BudgetRange;
use App\Enums\JobCategory;
use App\Enums\JobUrgency;
use App\Enums\PropertyType;

trait ProvidesJobRequestOptions
{
    /**
     * Get the select options for the job request form.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    protected function jobRequestOptions(): array
    {
        return [
            'categories' => JobCategory::options(),
            'urgencies' => JobUrgency::options(),
            'budgetRanges' => BudgetRange::options(),
            'propertyTypes' => PropertyType::options(),
        ];
    }
}
