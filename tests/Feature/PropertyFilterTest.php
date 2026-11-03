<?php

use App\Enums\BookingPeriod;
use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\RoomTypePrice;
use Inertia\Testing\AssertableInertia as Assert;

function pricedProperty(int $price, BookingPeriod $period, bool $active = true): Property
{
    $property = Property::factory()->create(['status' => PropertyStatus::Published]);
    $roomType = RoomType::factory()->create(['property_id' => $property->id]);

    RoomTypePrice::factory()->create([
        'room_type_id' => $roomType->id,
        'period' => $period,
        'price' => $price,
        'is_active' => $active,
    ]);

    return $property;
}

test('the property filter matches the requested period', function () {
    $daily = pricedProperty(300_000, BookingPeriod::Daily);
    $monthly = pricedProperty(3_000_000, BookingPeriod::Monthly);

    $ids = Property::query()->filter(['period' => BookingPeriod::Daily->value])->pluck('id');

    expect($ids)->toContain($daily->id)
        ->and($ids)->not->toContain($monthly->id);
});

test('the property filter matches the price range of the requested period', function () {
    $cheap = pricedProperty(300_000, BookingPeriod::Daily);
    $expensive = pricedProperty(900_000, BookingPeriod::Daily);
    $inactive = pricedProperty(300_000, BookingPeriod::Daily, false);

    $ids = Property::query()->filter([
        'period' => BookingPeriod::Daily->value,
        'min_price' => 200_000,
        'max_price' => 600_000,
    ])->pluck('id');

    expect($ids)->toContain($cheap->id)
        ->and($ids)->not->toContain($expensive->id)
        ->and($ids)->not->toContain($inactive->id);
});

test('the property list page applies period and price filters', function () {
    $matching = pricedProperty(400_000, BookingPeriod::Monthly);
    pricedProperty(8_000_000, BookingPeriod::Monthly);

    $this->get(route('properties.index', [
        'period' => BookingPeriod::Monthly->value,
        'min_price' => 300_000,
        'max_price' => 5_000_000,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->has('properties.data', 1)
            ->where('properties.data.0.id', $matching->id)
            ->where('filters.period', BookingPeriod::Monthly->value));
});
