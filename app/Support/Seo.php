<?php

namespace App\Support;

/**
 * Builds a complete SEO payload for a page. The array is passed to Inertia as
 * the `seo` prop and rendered server-side in app.blade.php, so social scrapers
 * (which don't run JS) and search engines both receive correct per-page tags.
 */
class Seo
{
    /**
     * @param  array<string, mixed>|null  $schema  Page-level JSON-LD structured data
     * @return array<string, mixed>
     */
    public static function make(
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $ogImage = null,
        string $type = 'website',
        string $robots = 'index, follow',
        ?array $schema = null,
    ): array {
        $brand = config('velotor.brand');
        $suffix = $brand['name'];

        return [
            'title' => $title ? "{$title} — {$suffix}" : ($brand['full_name'].' — '.$brand['tagline']),
            'description' => $description ?: $brand['description'],
            'canonical' => $canonical ?: url()->current(),
            'og_image' => $ogImage ?: asset('images/og-image.jpg'),
            'type' => $type,
            'robots' => $robots,
            'schema' => $schema,
        ];
    }
}
