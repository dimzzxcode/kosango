<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Page;
use App\Models\Property;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    public function show(City $city): Response
    {
        $properties = Property::query()
            ->published()
            ->where('city_id', $city->id)
            ->with(['city', 'roomTypes.prices'])
            ->orderByDesc('rating_avg')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('cities/Show', [
            'city' => $city,
            'properties' => $properties,
            'pages' => Page::query()->get(['slug', 'title']),
        ]);
    }
}
