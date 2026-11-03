<?php

namespace App\Http\Controllers;

use App\Enums\PropertyStatus;
use App\Models\City;
use App\Models\Page;
use App\Models\Property;
use App\Models\RoomTypePrice;
use App\Repositories\PropertyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(private readonly PropertyRepository $properties) {}

    /**
     * Show the searchable property directory.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['city', 'type', 'q', 'guests', 'period', 'min_price', 'max_price']);

        return Inertia::render('properties/Index', [
            'properties' => $this->properties->search($filters),
            'cities' => City::query()->withCount('properties')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Show a property together with its room types and rates.
     */
    public function show(Request $request, Property $property): Response
    {
        abort_unless(
            $property->status === PropertyStatus::Published
                || ($request->user()?->canManageProperty($property) ?? false),
            404,
        );

        $property->load([
            'city',
            'images',
            'facilities',
            'roomTypes.prices',
            'roomTypes.rooms',
            'reviews' => fn ($query) => $query->where('is_visible', true)->latest()->with('user'),
            'bankAccounts' => fn ($query) => $query->where('is_active', true),
        ]);

        $similar = Property::query()
            ->published()
            ->with(['city', 'roomTypes.prices'])
            ->where('city_id', $property->city_id)
            ->whereKeyNot($property->getKey())
            ->orderByDesc('rating_avg')
            ->limit(3)
            ->get();

        return Inertia::render('properties/Show', [
            'property' => $property,
            'similarProperties' => $similar,
            'pages' => Page::query()->get(['slug', 'title']),
            'priceRange' => $this->priceRange($property),
        ]);
    }

    /**
     * @return array{min: int, max: int, unit: string}|null
     */
    private function priceRange(Property $property): ?array
    {
        $daily = RoomTypePrice::query()
            ->whereIn('room_type_id', $property->roomTypes()->select('id'))
            ->where('period', 'daily')
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        /** @var Collection<int, RoomTypePrice> $rates */
        $rates = $daily->isNotEmpty() ? $daily : $this->shortestAvailableRates($property);

        if ($rates->isEmpty()) {
            return null;
        }

        return [
            'min' => (int) $rates->min('price'),
            'max' => (int) $rates->max('price'),
            'unit' => $rates->first()->period->unitLabel(),
        ];
    }

    /**
     * @return Collection<int, RoomTypePrice>
     */
    private function shortestAvailableRates(Property $property): Collection
    {
        foreach (['daily', 'monthly', 'yearly'] as $period) {
            $rates = RoomTypePrice::query()
                ->whereIn('room_type_id', $property->roomTypes()->select('id'))
                ->where('period', $period)
                ->where('is_active', true)
                ->get();

            if ($rates->isNotEmpty()) {
                return $rates;
            }
        }

        return collect();
    }
}
