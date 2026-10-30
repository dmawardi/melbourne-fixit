<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business Details
    |--------------------------------------------------------------------------
    |
    | Public details shown in the site footer and published in structured
    | data, the sitemap and llms.txt. Leave a value empty to leave it out
    | rather than publishing a placeholder.
    |
    */

    'name' => env('APP_NAME', 'Melbourne Fixit'),

    'description' => 'Home maintenance, repairs and renovations across Melbourne. Tell us about the job and we\'ll match it with a trusted local tradesperson and send you a quote.',

    'phone' => env('BUSINESS_PHONE'),

    'email' => env('BUSINESS_EMAIL'),

    'abn' => env('BUSINESS_ABN'),

    // The region served. Used for areaServed in structured data.
    'city' => 'Melbourne',
    'region' => 'VIC',
    'country' => 'AU',

    'service_areas' => [
        'Melbourne CBD and inner suburbs',
        'Northern suburbs',
        'Eastern suburbs',
        'South-eastern suburbs',
        'Bayside',
        'Western suburbs',
    ],

    // Absolute or root-relative URLs, e.g. a Facebook page or Google Business Profile.
    'same_as' => array_values(array_filter(explode(',', (string) env('BUSINESS_SAME_AS', '')))),

    // Shown when a page is shared on social media. 1200x630 pixels.
    'og_image' => '/og-image.png',

];
