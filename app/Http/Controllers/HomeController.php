<?php

namespace App\Http\Controllers;

use App\Enums\JobCategory;
use App\Support\Faqs;
use App\Support\StructuredData;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the home page.
     */
    public function __invoke(): Response
    {
        $faqs = Faqs::all();

        return Inertia::render('home', [
            'services' => JobCategory::services(),
            'serviceAreas' => config('business.service_areas'),
            'faqs' => $faqs,
            'structuredData' => [
                StructuredData::business(),
                StructuredData::website(),
                StructuredData::faqPage($faqs),
            ],
        ]);
    }
}
