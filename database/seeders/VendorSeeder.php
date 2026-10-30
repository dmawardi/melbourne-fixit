<?php

namespace Database\Seeders;

use App\Enums\JobCategory;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Seed a mix of general and specialist vendors for local development.
     */
    public function run(): void
    {
        Vendor::factory()->general()->create(['name' => 'Melbourne Property Services']);
        Vendor::factory()->general()->create(['name' => 'Bayside Building Group']);
        Vendor::factory()->specialist(JobCategory::Plumbing)->create(['name' => 'Northside Plumbing']);
        Vendor::factory()->specialist(JobCategory::Electrical)->create(['name' => 'Brightline Electrical']);
        Vendor::factory()->specialist(JobCategory::Painting, JobCategory::Plastering)->create(['name' => 'Fresh Coat Painters']);
        Vendor::factory()->specialist(JobCategory::Fencing, JobCategory::Landscaping)->create(['name' => 'Greenfield Outdoors']);
    }
}
