<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'freq' => 'weekly'],
            ['loc' => route('stat.home'), 'priority' => '0.9', 'freq' => 'daily'],
            ['loc' => route('stat.week.archive'), 'priority' => '0.6', 'freq' => 'weekly'],
            ['loc' => route('stat.year.show'), 'priority' => '0.7', 'freq' => 'weekly'],
            ['loc' => route('stat.all-time'), 'priority' => '0.7', 'freq' => 'weekly'],
            ['loc' => route('stat.rules'), 'priority' => '0.4', 'freq' => 'monthly'],
            ['loc' => route('privacy'), 'priority' => '0.3', 'freq' => 'yearly'],
        ];

        foreach (Participant::whereNotNull('slug')->orderBy('id')->get() as $participant) {
            $urls[] = [
                'loc' => route('stat.participants.show', $participant->slug),
                'priority' => '0.5',
                'freq' => 'weekly',
                'lastmod' => optional($participant->last_seen_at)->toDateString(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($url['loc']).'</loc>'."\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$url['freq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
