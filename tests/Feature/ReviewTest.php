<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;

function completedBooking(User $user, ?Property $property = null, ?Booking $parent = null): Booking
{
    $property ??= Property::factory()->create();

    return Booking::factory()->completed()->create([
        'property_id' => $property->id,
        'user_id' => $user->id,
        'extended_from_id' => $parent?->id,
        'check_in' => now()->subDays(5)->startOfDay(),
        'check_out' => now()->subDay()->startOfDay(),
    ]);
}

test('a customer can review a completed booking', function () {
    $user = User::factory()->customer()->create();
    $booking = completedBooking($user);

    $this->actingAs($user)
        ->post(route('reviews.store', $booking->code), [
            'rating' => 5,
            'comment' => 'Kamar bersih dan nyaman.',
        ])
        ->assertRedirect();

    expect($booking->review()->exists())->toBeTrue()
        ->and($booking->review->rating)->toBe(5);
});

test('a parent booking cannot be reviewed when its extension is completed', function () {
    $user = User::factory()->customer()->create();
    $property = Property::factory()->create();
    $parent = completedBooking($user, $property);
    $extension = completedBooking($user, $property, $parent);

    $this->actingAs($user)
        ->post(route('reviews.store', $parent->code), ['rating' => 4])
        ->assertSessionHasErrors('booking');

    expect(Review::query()->count())->toBe(0)
        ->and($extension->status)->toBe(BookingStatus::Completed);
});

test('the last completed booking of an extension chain can be reviewed', function () {
    $user = User::factory()->customer()->create();
    $property = Property::factory()->create();
    $parent = completedBooking($user, $property);
    $extension = completedBooking($user, $property, $parent);

    $this->actingAs($user)
        ->post(route('reviews.store', $extension->code), ['rating' => 5])
        ->assertSessionHasNoErrors();

    expect($extension->review()->exists())->toBeTrue()
        ->and($parent->review()->exists())->toBeFalse();
});

test('a booking can only receive one review', function () {
    $user = User::factory()->customer()->create();
    $booking = completedBooking($user);

    $this->actingAs($user)
        ->post(route('reviews.store', $booking->code), ['rating' => 4])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('reviews.store', $booking->code), ['rating' => 5])
        ->assertSessionHasErrors('booking');

    expect(Review::query()->count())->toBe(1);
});
