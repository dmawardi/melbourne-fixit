<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const TAG_URL = 'https://www.googletagmanager.com/gtag/js?id=G-TEST123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_tag_renders_on_public_pages_without_automatic_page_views(): void
    {
        config(['services.google_analytics.measurement_id' => 'G-TEST123']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(self::TAG_URL, false)
            ->assertSee('send_page_view: false', false);
    }

    public function test_the_tag_is_not_rendered_on_auth_pages(): void
    {
        config(['services.google_analytics.measurement_id' => 'G-TEST123']);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    public function test_the_tag_is_not_rendered_without_a_measurement_id(): void
    {
        config(['services.google_analytics.measurement_id' => null]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }
}
