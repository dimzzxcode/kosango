<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('a superadmin can reorder gallery images', function () {
    $superadmin = User::factory()->superadmin()->create();
    $property = Property::factory()->create();

    $first = PropertyImage::query()->create([
        'property_id' => $property->id,
        'path' => 'properties/first.jpg',
        'alt' => $property->name,
        'sort_order' => 1,
    ]);
    $second = PropertyImage::query()->create([
        'property_id' => $property->id,
        'path' => 'properties/second.jpg',
        'alt' => $property->name,
        'sort_order' => 2,
    ]);
    $third = PropertyImage::query()->create([
        'property_id' => $property->id,
        'path' => 'properties/third.jpg',
        'alt' => $property->name,
        'sort_order' => 3,
    ]);

    $this->actingAs($superadmin)
        ->put(route('superadmin.properties.images.order', $property->slug), [
            'ids' => [$third->id, $first->id, $second->id],
        ])
        ->assertRedirect();

    expect($property->images()->pluck('id')->all())
        ->toBe([$third->id, $first->id, $second->id]);
});

test('gallery reordering rejects images from another property', function () {
    $superadmin = User::factory()->superadmin()->create();
    $property = Property::factory()->create();
    $other = Property::factory()->create();

    $own = PropertyImage::query()->create([
        'property_id' => $property->id,
        'path' => 'properties/own.jpg',
        'alt' => $property->name,
        'sort_order' => 1,
    ]);
    $foreign = PropertyImage::query()->create([
        'property_id' => $other->id,
        'path' => 'properties/foreign.jpg',
        'alt' => $other->name,
        'sort_order' => 1,
    ]);

    $this->actingAs($superadmin)
        ->put(route('superadmin.properties.images.order', $property->slug), [
            'ids' => [$foreign->id],
        ])
        ->assertSessionHasErrors('ids');

    expect($property->images()->pluck('id')->all())->toBe([$own->id]);
});

test('force deleting a property removes its stored images', function () {
    Storage::fake('public');

    $property = Property::factory()->create(['thumbnail_path' => 'properties/thumb.jpg']);
    Storage::disk('public')->put('properties/thumb.jpg', 'thumb');

    PropertyImage::query()->create([
        'property_id' => $property->id,
        'path' => 'properties/gallery.jpg',
        'alt' => $property->name,
        'sort_order' => 1,
    ]);
    Storage::disk('public')->put('properties/gallery.jpg', 'gallery');

    $property->forceDelete();

    Storage::disk('public')->assertMissing('properties/thumb.jpg');
    Storage::disk('public')->assertMissing('properties/gallery.jpg');
});

test('force deleting payments and refunds removes their private proofs', function () {
    Storage::fake('local');

    $booking = Booking::factory()->create();
    $invoice = Invoice::factory()->create(['booking_id' => $booking->id]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'proof_path' => 'payment-proofs/proof.jpg',
        'status' => 'paid',
    ]);
    Storage::disk('local')->put('payment-proofs/proof.jpg', 'proof');

    $refund = Refund::factory()->create([
        'booking_id' => $booking->id,
        'proof_path' => 'refund-proofs/proof.jpg',
    ]);
    Storage::disk('local')->put('refund-proofs/proof.jpg', 'proof');

    $payment->forceDelete();
    $refund->forceDelete();

    Storage::disk('local')->assertMissing('payment-proofs/proof.jpg');
    Storage::disk('local')->assertMissing('refund-proofs/proof.jpg');
});
