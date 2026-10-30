<?php

namespace App\Http\Controllers;

use App\Enums\JobCategory;
use App\Http\Middleware\SetRobotsTag;
use App\Support\Faqs;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Paths crawlers have no reason to visit, since they require logging in.
     *
     * @var list<string>
     */
    protected const DISALLOWED_PATHS = [
        '/admin',
        '/requests',
        '/settings',
    ];

    /**
     * Serve robots.txt. Outside production, all crawling is blocked so staging sites stay out of search results.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->isProduction()) {
            foreach (self::DISALLOWED_PATHS as $path) {
                $lines[] = "Disallow: {$path}";
            }

            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Serve the XML sitemap of indexable pages.
     */
    public function sitemap(): Response
    {
        $urls = array_map(fn (string $name): string => route($name), SetRobotsTag::INDEXABLE_ROUTES);

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Serve llms.txt: a plain-language summary of the site for AI assistants and answer engines.
     *
     * @see https://llmstxt.org
     */
    public function llms(): Response
    {
        return response()
            ->view('seo.llms', [
                'business' => config('business'),
                'services' => JobCategory::services(),
                'faqs' => Faqs::all(),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
