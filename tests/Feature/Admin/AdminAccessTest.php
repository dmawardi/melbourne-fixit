<?php

namespace Tests\Feature\Admin;

use App\Models\JobRequest;
use App\Models\JobRequestFile;
use App\Models\JobRequestMessage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'index' => ['get', 'admin.job-requests.index'],
            'show' => ['get', 'admin.job-requests.show'],
            'update' => ['patch', 'admin.job-requests.update'],
            'destroy' => ['delete', 'admin.job-requests.destroy'],
            'files.store' => ['post', 'admin.job-requests.files.store'],
            'files.update' => ['patch', 'admin.job-requests.files.update'],
            'files.destroy' => ['delete', 'admin.job-requests.files.destroy'],
            'messages.store' => ['post', 'admin.job-requests.messages.store'],
            'messages.destroy' => ['delete', 'admin.job-requests.messages.destroy'],
            'vendor.update' => ['put', 'admin.job-requests.vendor.update'],
            'vendor.destroy' => ['delete', 'admin.job-requests.vendor.destroy'],
            'vendors.index' => ['get', 'admin.vendors.index'],
            'vendors.create' => ['get', 'admin.vendors.create'],
            'vendors.store' => ['post', 'admin.vendors.store'],
            'vendors.edit' => ['get', 'admin.vendors.edit'],
            'vendors.update' => ['put', 'admin.vendors.update'],
            'vendors.destroy' => ['delete', 'admin.vendors.destroy'],
        ];
    }

    private function urlFor(string $route): string
    {
        $jobRequest = JobRequest::factory()->create();
        $file = JobRequestFile::factory()->for($jobRequest)->create();
        $message = JobRequestMessage::factory()->for($jobRequest)->create();

        return route($route, match (true) {
            str_contains($route, 'files.store'), str_contains($route, 'messages.store') => [$jobRequest],
            str_contains($route, 'files.') => [$jobRequest, $file],
            str_contains($route, 'messages.') => [$jobRequest, $message],
            in_array($route, ['admin.vendors.index', 'admin.vendors.create', 'admin.vendors.store'], true) => [],
            str_contains($route, 'admin.vendors.') => [Vendor::factory()->create()],
            $route === 'admin.job-requests.index' => [],
            default => [$jobRequest],
        });
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_login(string $method, string $route)
    {
        $this->{$method}($this->urlFor($route))->assertRedirect(route('login'));
    }

    #[DataProvider('adminRoutes')]
    public function test_non_admins_are_forbidden(string $method, string $route)
    {
        $url = $this->urlFor($route);

        $this->actingAs(User::factory()->create())->{$method}($url)->assertForbidden();
    }

    public function test_admins_cannot_use_customer_routes_for_requests_they_do_not_own()
    {
        $jobRequest = JobRequest::factory()->claimedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('job-requests.show', $jobRequest))
            ->assertForbidden();
    }

    public function test_is_admin_cannot_be_mass_assigned()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => true,
        ]);

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_make_admin_command_grants_and_revokes_access()
    {
        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->artisan('app:make-admin', ['email' => 'Staff@Example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());

        $this->artisan('app:make-admin', ['email' => 'staff@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_make_admin_command_fails_for_unknown_users()
    {
        $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
