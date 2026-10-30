<?php

namespace Tests\Feature;

use App\Models\JobRequest;
use App\Models\User;
use App\Support\Faqs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function inProduction(): void
    {
        $this->app['env'] = 'production';
    }

    public function test_the_home_page_includes_services_faqs_and_structured_data(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home')
                ->has('services', 13)
                ->has('faqs', count(Faqs::all()))
                ->where('structuredData.0.@type', 'HomeAndConstructionBusiness')
                ->where('structuredData.0.areaServed.name', 'Melbourne')
                ->has('structuredData.0.hasOfferCatalog.itemListElement', 13)
                ->where('structuredData.1.@type', 'WebSite')
                ->where('structuredData.2.@type', 'FAQPage')
                ->has('structuredData.2.mainEntity', count(Faqs::all()))
            );
    }

    public function test_unset_business_details_are_left_out_of_structured_data(): void
    {
        config(['business.phone' => null, 'business.email' => '']);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('structuredData.0.telephone')
                ->missing('structuredData.0.email')
                ->missing('structuredData.0.sameAs')
            );

        config(['business.phone' => '03 9000 0000']);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('structuredData.0.telephone', '03 9000 0000')
            );
    }

    public function test_the_quote_form_includes_breadcrumbs_and_preselects_a_valid_category(): void
    {
        $this->get(route('job-requests.create', ['category' => 'tiling']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('defaults.category', 'tiling')
                ->where('structuredData.0.@type', 'BreadcrumbList')
                ->has('structuredData.0.itemListElement', 2)
            );

        $this->get(route('job-requests.create', ['category' => 'not-a-category']))
            ->assertInertia(fn (Assert $page) => $page->where('defaults.category', null));
    }

    public function test_public_pages_are_indexable_in_production(): void
    {
        $this->inProduction();

        $this->get(route('home'))->assertHeaderMissing('X-Robots-Tag');
        $this->get(route('job-requests.create'))->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_private_and_auth_pages_are_never_indexed(): void
    {
        $this->inProduction();

        $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('register'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)
            ->get(route('job-requests.show', $jobRequest))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_nothing_is_indexed_outside_production(): void
    {
        $this->get(route('home'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_txt_allows_crawling_in_production_and_links_the_sitemap(): void
    {
        $this->inProduction();

        $response = $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertStringContainsString('Disallow: /admin', $response->getContent());
        $this->assertStringContainsString('Disallow: /requests', $response->getContent());
        $this->assertStringNotContainsString("Disallow: /\n", $response->getContent());
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $response->getContent());
    }

    public function test_robots_txt_blocks_all_crawling_outside_production(): void
    {
        $this->assertSame("User-agent: *\nDisallow: /\n", $this->get('/robots.txt')->getContent());
    }

    public function test_the_sitemap_lists_only_public_pages(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);

        $locs = [];
        foreach ($xml->url as $url) {
            $locs[] = (string) $url->loc;
        }

        $this->assertSame([route('home'), route('job-requests.create')], $locs);
    }

    public function test_llms_txt_describes_the_business_in_plain_text(): void
    {
        config(['business.email' => 'hello@example.com']);

        $content = $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertStringStartsWith('# '.config('business.name'), $content);
        $this->assertStringContainsString('Kitchen renovation:', $content);
        $this->assertStringContainsString(route('job-requests.create'), $content);
        $this->assertStringContainsString('Email: hello@example.com', $content);
        // Plain text, so apostrophes must not be HTML-escaped.
        $this->assertStringContainsString("we'll", $content);
        $this->assertStringNotContainsString('&#039;', $content);
    }
}
