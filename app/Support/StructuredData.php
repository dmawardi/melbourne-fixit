<?php

namespace App\Support;

use App\Enums\JobCategory;

/**
 * Builds schema.org JSON-LD for public pages, so search engines and AI assistants can
 * understand the business, its services and its service area.
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/local-business
 */
class StructuredData
{
    /**
     * The business, its services and the area it serves.
     *
     * @return array<string, mixed>
     */
    public static function business(): array
    {
        $business = config('business');
        $url = self::url('/');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'HomeAndConstructionBusiness',
            '@id' => $url.'#business',
            'name' => $business['name'],
            'description' => $business['description'],
            'url' => $url,
            'logo' => self::url('/apple-touch-icon.png'),
            'image' => self::url($business['og_image']),
            'telephone' => $business['phone'],
            'email' => $business['email'],
            // A service-area business: the locality is published, not a street address.
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $business['city'],
                'addressRegion' => $business['region'],
                'addressCountry' => $business['country'],
            ],
            'areaServed' => [
                '@type' => 'City',
                'name' => $business['city'],
                'containedInPlace' => ['@type' => 'State', 'name' => 'Victoria'],
            ],
            'sameAs' => $business['same_as'] ?: null,
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Home maintenance and renovation services',
                'itemListElement' => array_map(fn (array $service): array => [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $service['label'],
                        'description' => $service['description'],
                        'areaServed' => $business['city'],
                    ],
                ], JobCategory::services()),
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * The website, linked to the business that publishes it.
     *
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('business.name'),
            'url' => self::url('/'),
            'inLanguage' => 'en-AU',
            'publisher' => ['@id' => self::url('/').'#business'],
        ];
    }

    /**
     * Frequently asked questions. They must match the questions shown on the page.
     *
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>
     */
    public static function faqPage(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs),
        ];
    }

    /**
     * Breadcrumbs for a page, from the home page down.
     *
     * @param  array<string, string>  $crumbs  Names keyed by URL.
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $position = 0;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(function (string $url, string $name) use (&$position): array {
                return [
                    '@type' => 'ListItem',
                    'position' => ++$position,
                    'name' => $name,
                    'item' => $url,
                ];
            }, array_keys($crumbs), $crumbs),
        ];
    }

    /**
     * Make an absolute URL from a path on the site.
     */
    protected static function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
