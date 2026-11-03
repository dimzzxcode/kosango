<?php

use App\Models\City;
use App\Models\Page;
use App\Models\Property;

test('robots.txt allows public pages and blocks private areas', function () {
    $response = $this->get(route('robots'));

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/plain');

    $response->assertSee('User-agent: *', false);
    $response->assertSee('Allow: /', false);
    $response->assertSee('Disallow: /dashboard', false);
    $response->assertSee('Disallow: /bookings', false);
    $response->assertSee('Disallow: /admin', false);
    $response->assertSee('Disallow: /superadmin', false);
    $response->assertSee('Disallow: /settings', false);
    $response->assertSee('Sitemap: '.route('sitemap'), false);
});

test('sitemap.xml lists published properties, cities, and pages', function () {
    $city = City::factory()->create();
    $property = Property::factory()->create(['city_id' => $city->id]);
    $page = Page::query()->create([
        'slug' => 'syarat-ketentuan',
        'title' => 'Syarat & Ketentuan',
        'body' => 'Ketentuan penggunaan KosanGo.',
        'version' => 1,
    ]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/xml');

    $response->assertSee(route('home'), false);
    $response->assertSee(route('properties.index'), false);
    $response->assertSee(route('properties.show', $property->slug), false);
    $response->assertSee(route('cities.show', $city->slug), false);
    $response->assertSee(route('pages.show', $page->slug), false);
});

test('sitemap.xml omits unpublished properties', function () {
    $draft = Property::factory()->draft()->create();

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertDontSee(route('properties.show', $draft->slug), false);
});
