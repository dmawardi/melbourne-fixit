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
        Vendor::factory()->withDocuments()->general()->create(['name' => 'Melbourne Property Services']);
        Vendor::factory()->withDocuments()->general()->create([
            'name' => 'Bayside Building Group',
            'public_liability_expires_on' => now()->subWeek(),
        ]);
        Vendor::factory()->withDocuments()->specialist(JobCategory::Plumbing)->create([
            'name' => 'Northside Plumbing',
            'plumbing_licence_number' => '54321',
            'plumbing_licence_expires_on' => now()->addYear(),
        ]);
        Vendor::factory()->withDocuments()->specialist(JobCategory::Electrical)->create([
            'name' => 'Brightline Electrical',
            'electrical_registration_number' => 'REC 12345',
            'electrical_registration_expires_on' => now()->addYear(),
        ]);
        Vendor::factory()->withDocuments()->specialist(JobCategory::Painting, JobCategory::Plastering)->create([
            'name' => 'Fresh Coat Painters',
            'workcover_expires_on' => now()->addDays(20),
        ]);
        Vendor::factory()->withDocuments()->specialist(JobCategory::Fencing, JobCategory::Landscaping)->create([
            'name' => 'Greenfield Outdoors',
            'documents_checked_on' => null,
        ]);
    }
}
