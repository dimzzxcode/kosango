<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Page;
use App\Models\Property;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('properties.index'), 'priority' => '0.9'],
        ];

        Property::query()->published()->get(['slug', 'updated_at'])->each(
            function (Property $property) use (&$urls): void {
                $urls[] = [
                    'loc' => route('properties.show', $property->slug),
                    'lastmod' => $property->updated_at?->toAtomString(),
                    'priority' => '0.8',
                ];
            },
        );

        City::query()->get(['slug'])->each(
            function (City $city) use (&$urls): void {
                $urls[] = ['loc' => route('cities.show', $city->slug), 'priority' => '0.7'];
            },
        );

        Page::query()->get(['slug'])->each(
            function ($page) use (&$urls): void {
                $urls[] = ['loc' => route('pages.show', $page->slug), 'priority' => '0.4'];
            },
        );

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /bookings',
            'Disallow: /admin',
            'Disallow: /superadmin',
            'Disallow: /settings',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
