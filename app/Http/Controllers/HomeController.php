<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\City;
use App\Models\Page;
use App\Repositories\PropertyRepository;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly PropertyRepository $properties) {}

    /**
     * Show the KosanGo landing page.
     */
    public function index(): Response
    {
        $cities = City::query()
            ->withCount('properties')
            ->orderBy('name')
            ->get();

        return Inertia::render('Welcome', [
            'cities' => $cities,
            'allCities' => City::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'properties' => $this->properties->featured(),
            'stats' => [
                'cities' => City::query()->count(),
                'properties' => $this->properties->publishedCount(),
                'bookings' => Booking::query()->count(),
            ],
            'pages' => Page::query()->get(['slug', 'title']),
        ]);
    }
}
