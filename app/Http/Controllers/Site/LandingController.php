<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $brand = config('velotor.brand');

        return Inertia::render('Landing', [
            'stats' => [
                'participants' => Participant::count(),
                'total_distance' => round((float) RideResult::sum('distance_km')),
                'total_rides' => RideResult::count(),
            ],
            'seo' => Seo::make(
                description: $brand['description'],
                type: 'website',
                schema: [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $brand['full_name'],
                    'url' => $brand['site_url'],
                    'inLanguage' => 'uk-UA',
                    'description' => $brand['description'],
                ],
            ),
        ]);
    }
}
