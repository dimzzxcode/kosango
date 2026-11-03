<?php

namespace Database\Seeders;

use App\Actions\ApprovePayment;
use App\Actions\CancelBooking;
use App\Actions\CreateBooking;
use App\Actions\SubmitPaymentProof;
use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentPlan;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\RefundInitiator;
use App\Enums\RoomStatus;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\City;
use App\Models\Facility;
use App\Models\Page;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KosanSeeder extends Seeder
{
    /**
     * Seed master data, accounts, and demo bookings.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $superadmin = User::query()->where('email', 'superadmin@kosango.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kosango.test')->firstOrFail();
        $customer = User::query()->where('email', 'test@example.com')->firstOrFail();

        $cities = collect([
            ['name' => 'Jakarta', 'slug' => 'jakarta', 'province' => 'DKI Jakarta'],
            ['name' => 'Bandung', 'slug' => 'bandung', 'province' => 'Jawa Barat'],
            ['name' => 'Yogyakarta', 'slug' => 'yogyakarta', 'province' => 'DI Yogyakarta'],
            ['name' => 'Surabaya', 'slug' => 'surabaya', 'province' => 'Jawa Timur'],
            ['name' => 'Denpasar', 'slug' => 'denpasar', 'province' => 'Bali'],
            ['name' => 'Malang', 'slug' => 'malang', 'province' => 'Jawa Timur'],
        ])->mapWithKeys(fn (array $city): array => [
            $city['slug'] => City::query()->updateOrCreate(['slug' => $city['slug']], $city),
        ]);

        $facilities = collect([
            'WiFi cepat', 'AC', 'Dapur bersama', 'Laundry', 'Parkir',
            'Keamanan 24 jam', 'Sarapan', 'Kolam renang', 'Gym',
            'Resepsionis 24 jam', 'Restoran', 'Taman', 'Balkon',
            'Ruang kerja', 'Coworking', 'Spa',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Facility::query()->firstOrCreate(['name' => $name]),
        ]);

        $definitions = $this->propertyDefinitions();

        foreach ($definitions as $definition) {
            $this->seedProperty($definition, $cities, $facilities, $superadmin);
        }

        $this->assignAdmins($admin);

        BankAccount::query()->firstOrCreate(
            ['bank_name' => 'BCA', 'account_number' => '1234567890'],
            ['account_holder' => 'KosanGo', 'is_active' => true],
        );
        BankAccount::query()->firstOrCreate(
            ['bank_name' => 'Mandiri', 'account_number' => '9876543210'],
            ['account_holder' => 'KosanGo', 'is_active' => true],
        );

        Page::query()->updateOrCreate(
            ['slug' => 'syarat-ketentuan'],
            [
                'title' => 'Syarat dan Ketentuan',
                'version' => (int) config('booking.terms_version'),
                'body' => $this->termsBody(),
            ],
        );
        Page::query()->updateOrCreate(
            ['slug' => 'kebijakan-privasi'],
            [
                'title' => 'Kebijakan Privasi',
                'body' => $this->privacyBody(),
            ],
        );

        $this->seedDemoBookings($customer, $admin);

        $superadmin->forceFill(['email_verified_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, City>  $cities
     * @param  Collection<string, Facility>  $facilities
     */
    private function seedProperty(array $definition, $cities, $facilities, User $superadmin): void
    {
        $property = Property::query()->updateOrCreate(
            ['slug' => $definition['slug']],
            [
                'city_id' => $cities[$definition['city']]->id,
                'created_by' => $superadmin->id,
                'type' => $definition['type'],
                'name' => $definition['name'],
                'description' => $definition['description'],
                'address' => $definition['address'],
                'latitude' => $definition['latitude'],
                'longitude' => $definition['longitude'],
                'whatsapp_number' => '6281234567890',
                'status' => PropertyStatus::Published,
                'thumbnail_path' => $this->seedThumbnail($definition['slug'], $definition['name']),
                'thumbnail_alt' => $definition['name'],
                'rating_avg' => 0,
                'reviews_count' => 0,
            ],
        );

        $property->facilities()->sync(
            collect($definition['amenities'])
                ->map(fn (string $name) => $facilities[$name]->id)
                ->all(),
        );

        foreach ($this->imageAccents() as $index => $accent) {
            $property->images()->firstOrCreate(
                ['path' => "properties/{$definition['slug']}-{$index}.svg"],
                [
                    'alt' => $definition['name'].' - foto '.($index + 1),
                    'sort_order' => $index,
                ],
            );
        }

        foreach ($definition['rooms'] as $roomIndex => $roomDefinition) {
            $roomType = RoomType::query()->updateOrCreate(
                ['property_id' => $property->id, 'name' => $roomDefinition['name']],
                [
                    'description' => $roomDefinition['description'],
                    'capacity' => $roomDefinition['capacity'],
                ],
            );

            $dailyPrice = $roomDefinition['daily_price'];

            foreach ([
                [BookingPeriod::Daily, $dailyPrice, 1, 30],
                [BookingPeriod::Monthly, (int) round($dailyPrice * 26), 1, 24],
                [BookingPeriod::Yearly, (int) round($dailyPrice * 280), 1, 5],
            ] as [$period, $price, $min, $max]) {
                $roomType->prices()->updateOrCreate(
                    ['period' => $period],
                    [
                        'price' => $price,
                        'min_duration' => $min,
                        'max_duration' => $max,
                        'is_active' => true,
                    ],
                );
            }

            for ($roomNumber = 1; $roomNumber <= $roomDefinition['rooms']; $roomNumber++) {
                Room::query()->updateOrCreate(
                    [
                        'property_id' => $property->id,
                        'name' => sprintf('%s-%02d', Str::upper(Str::substr($roomDefinition['name'], 0, 3)), $roomIndex * 10 + $roomNumber),
                    ],
                    [
                        'room_type_id' => $roomType->id,
                        'status' => RoomStatus::Available,
                    ],
                );
            }
        }
    }

    private function assignAdmins(User $admin): void
    {
        $admin->assignedProperties()->sync(
            Property::query()->pluck('id')->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $room
     */
    private function demoBookingData(Property $property, RoomType $roomType, int $checkInOffset, BookingPeriod $period, int $duration, PaymentPlan $plan): array
    {
        $room = $roomType->rooms()->where('status', RoomStatus::Available->value)->firstOrFail();

        return [
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays($checkInOffset)->toDateString(),
            'duration' => $duration,
            'period' => $period->value,
            'payment_plan' => $plan->value,
            'guests' => min(2, $roomType->capacity),
            'phone' => '081234567890',
            'note' => null,
        ];
    }

    private function seedDemoBookings(User $customer, User $admin): void
    {
        if (Booking::query()->where('user_id', $customer->id)->exists()) {
            return;
        }

        $createBooking = app(CreateBooking::class);
        $submitProof = app(SubmitPaymentProof::class);
        $approvePayment = app(ApprovePayment::class);
        $cancelBooking = app(CancelBooking::class);

        $pendingProperty = Property::query()->where('slug', 'grand-sabang-hotel')->firstOrFail();
        $pendingType = $pendingProperty->roomTypes()->where('name', 'Superior Room')->firstOrFail();
        $createBooking->handle(
            $customer,
            $this->demoBookingData($pendingProperty, $pendingType, 5, BookingPeriod::Daily, 2, PaymentPlan::Full),
        );

        $confirmedProperty = Property::query()->where('slug', 'malioboro-stay')->firstOrFail();
        $confirmedType = $confirmedProperty->roomTypes()->where('name', 'Smart Room')->firstOrFail();
        $confirmed = $createBooking->handle(
            $customer,
            $this->demoBookingData($confirmedProperty, $confirmedType, 10, BookingPeriod::Daily, 3, PaymentPlan::Full),
        );
        $this->payFirstInvoice($confirmed, $submitProof, $approvePayment, $customer, $admin);

        $completedProperty = Property::query()->where('slug', 'kemang-cozy-kos')->firstOrFail();
        $completedType = $completedProperty->roomTypes()->where('name', 'Kamar Standard')->firstOrFail();
        $completed = $createBooking->handle(
            $customer,
            $this->demoBookingData($completedProperty, $completedType, -40, BookingPeriod::Monthly, 1, PaymentPlan::Full),
        );
        $this->payFirstInvoice($completed, $submitProof, $approvePayment, $customer, $admin);
        $completed->forceFill([
            'status' => BookingStatus::Completed,
            'confirmed_at' => now()->subDays(40),
            'completed_at' => now()->subDays(9),
        ])->save();

        $cancelledProperty = Property::query()->where('slug', 'seminyak-breeze-resort')->firstOrFail();
        $cancelledType = $cancelledProperty->roomTypes()->where('name', 'Garden Villa')->firstOrFail();
        $cancelled = $createBooking->handle(
            $customer,
            $this->demoBookingData($cancelledProperty, $cancelledType, 30, BookingPeriod::Daily, 2, PaymentPlan::Full),
        );
        $this->payFirstInvoice($cancelled, $submitProof, $approvePayment, $customer, $admin);
        $cancelBooking->handle($cancelled, RefundInitiator::Customer, $customer, [
            'reason' => 'Rencana perjalanan berubah.',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => $customer->name,
        ]);
    }

    private function payFirstInvoice(Booking $booking, SubmitPaymentProof $submitProof, ApprovePayment $approvePayment, User $customer, User $admin): void
    {
        $invoice = $booking->invoices()->where('status', InvoiceStatus::Unpaid->value)->firstOrFail();

        $file = UploadedFile::fake()->create('bukti-transfer.png', 120, 'image/png');

        $payment = $submitProof->handle(
            $customer,
            $booking,
            $invoice,
            $invoice->remaining(),
            $file,
        );

        $approvePayment->handle($payment, $admin);
    }

    private function seedThumbnail(string $slug, string $name): string
    {
        $path = "properties/{$slug}.svg";
        $accent = $this->accentFor($slug);
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$accent[0]}"/>
      <stop offset="100%" stop-color="{$accent[1]}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#g)"/>
  <circle cx="980" cy="120" r="180" fill="rgba(255,255,255,0.12)"/>
  <circle cx="180" cy="720" r="220" fill="rgba(0,0,0,0.10)"/>
  <text x="70" y="640" font-family="Arial, sans-serif" font-size="64" font-weight="700" fill="#ffffff">{$name}</text>
  <text x="70" y="700" font-family="Arial, sans-serif" font-size="28" fill="rgba(255,255,255,0.8)">KosanGo</text>
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function accentFor(string $slug): array
    {
        $palettes = [
            ['#059669', '#0d9488'],
            ['#0284c7', '#4338ca'],
            ['#f97316', '#e11d48'],
            ['#8b5cf6', '#c026d3'],
            ['#f59e0b', '#dc2626'],
        ];

        return $palettes[abs(crc32($slug)) % count($palettes)];
    }

    /**
     * @return array<int, string>
     */
    private function imageAccents(): array
    {
        return ['jade', 'ocean', 'sunset'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function propertyDefinitions(): array
    {
        return [
            [
                'slug' => 'kemang-cozy-kos', 'city' => 'jakarta', 'name' => 'Kos Kemang Cozy', 'type' => PropertyType::Kos,
                'address' => 'Jl. Kemang Selatan No. 18, Jakarta Selatan',
                'description' => 'Kos modern dengan dapur bersama, area kerja, dan keamanan 24 jam di jantung Kemang. Cocok untuk profesional muda yang ingin tinggal dekat kafe dan kantor.',
                'latitude' => -6.2608, 'longitude' => 106.8133,
                'amenities' => ['WiFi cepat', 'AC', 'Dapur bersama', 'Laundry', 'Parkir', 'Keamanan 24 jam'],
                'rooms' => [
                    ['name' => 'Kamar Standard', 'description' => 'Kamar single dengan kasur nyaman, meja kerja, dan kamar mandi dalam.', 'capacity' => 1, 'daily_price' => 150000, 'rooms' => 4],
                    ['name' => 'Kamar Deluxe', 'description' => 'Kamar lebih luas dengan jendela besar dan area duduk kecil.', 'capacity' => 2, 'daily_price' => 230000, 'rooms' => 3],
                    ['name' => 'Kamar Premium', 'description' => 'Kamar premium dengan balkon dan akses dapur bersama yang lebih privat.', 'capacity' => 2, 'daily_price' => 320000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'grand-sabang-hotel', 'city' => 'jakarta', 'name' => 'Hotel Grand Sabang', 'type' => PropertyType::Hotel,
                'address' => 'Jl. H. Agus Salim No. 22, Jakarta Pusat',
                'description' => 'Hotel bisnis klasik di kawasan Sabang dengan sarapan lokal, kolam renang kecil, dan akses mudah ke stasiun MRT.',
                'latitude' => -6.1845, 'longitude' => 106.8246,
                'amenities' => ['Sarapan', 'Kolam renang', 'Gym', 'Resepsionis 24 jam', 'WiFi cepat', 'Restoran'],
                'rooms' => [
                    ['name' => 'Superior Room', 'description' => 'Kamar superior dengan tempat tidur queen dan pemandangan kota.', 'capacity' => 2, 'daily_price' => 850000, 'rooms' => 5],
                    ['name' => 'Deluxe Room', 'description' => 'Kamar deluxe yang lebih luas dengan meja kerja dan sofa kecil.', 'capacity' => 2, 'daily_price' => 1250000, 'rooms' => 4],
                    ['name' => 'Suite Room', 'description' => 'Suite dengan ruang tamu terpisah dan akses lounge.', 'capacity' => 3, 'daily_price' => 2100000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'menteng-executive-hotel', 'city' => 'jakarta', 'name' => 'Hotel Menteng Executive', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Cikini Raya No. 45, Jakarta Pusat',
                'description' => 'Hotel tenang di Menteng dengan studio modern, cocok untuk tinggal sebulan atau lebih.',
                'latitude' => -6.1936, 'longitude' => 106.8372,
                'amenities' => ['WiFi cepat', 'AC', 'Dapur bersama', 'Laundry', 'Parkir'],
                'rooms' => [
                    ['name' => 'Studio Room', 'description' => 'Studio kompak dengan dapur kecil dan tempat kerja.', 'capacity' => 2, 'daily_price' => 1750000, 'rooms' => 3],
                    ['name' => 'Executive Room', 'description' => 'Kamar eksekutif dengan ruang tamu dan balkon.', 'capacity' => 2, 'daily_price' => 2600000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'dago-hill-kos', 'city' => 'bandung', 'name' => 'Kos Dago Hill', 'type' => PropertyType::Kos,
                'address' => 'Jl. Dago Asri No. 7, Bandung',
                'description' => 'Kos dengan pemandangan kota Bandung dari balkon bersama. Dekat kampus ITB dan kafe Dago.',
                'latitude' => -6.8844, 'longitude' => 107.6136,
                'amenities' => ['WiFi cepat', 'AC', 'Dapur bersama', 'Balkon', 'Parkir'],
                'rooms' => [
                    ['name' => 'Single Room', 'description' => 'Kamar single sederhana dengan meja belajar.', 'capacity' => 1, 'daily_price' => 120000, 'rooms' => 5],
                    ['name' => 'Deluxe Room', 'description' => 'Kamar deluxe dengan jendela besar menghadap kota.', 'capacity' => 2, 'daily_price' => 180000, 'rooms' => 3],
                    ['name' => 'Twin Room', 'description' => 'Dua kasur single untuk teman atau saudara.', 'capacity' => 2, 'daily_price' => 220000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'braga-heritage-hotel', 'city' => 'bandung', 'name' => 'Hotel Braga Heritage', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Braga No. 81, Bandung',
                'description' => 'Hotel butik di gedung heritage Braga dengan interior hangat dan sarapan khas Sunda.',
                'latitude' => -6.9175, 'longitude' => 107.6098,
                'amenities' => ['Sarapan', 'Restoran', 'WiFi cepat', 'Resepsionis 24 jam', 'Ruang kerja'],
                'rooms' => [
                    ['name' => 'Classic Room', 'description' => 'Kamar klasik dengan sentuhan kayu dan jendela kota.', 'capacity' => 2, 'daily_price' => 720000, 'rooms' => 4],
                    ['name' => 'Heritage Deluxe', 'description' => 'Kamar lebih luas dengan bathtub dan area baca.', 'capacity' => 2, 'daily_price' => 1100000, 'rooms' => 3],
                    ['name' => 'Family Room', 'description' => 'Kamar keluarga dengan dua tempat tidur.', 'capacity' => 4, 'daily_price' => 1650000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'riau-garden-hotel', 'city' => 'bandung', 'name' => 'Hotel Riau Garden', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Riau No. 120, Bandung',
                'description' => 'Hotel dengan taman kecil di tengah kota, cocok untuk keluarga kecil.',
                'latitude' => -6.9147, 'longitude' => 107.6186,
                'amenities' => ['WiFi cepat', 'Taman', 'Parkir', 'Sarapan'],
                'rooms' => [
                    ['name' => 'Garden Room', 'description' => 'Kamar menghadap taman dengan teras kecil.', 'capacity' => 2, 'daily_price' => 650000, 'rooms' => 3],
                    ['name' => 'Family Room', 'description' => 'Kamar keluarga dengan ruang tambahan.', 'capacity' => 4, 'daily_price' => 1050000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'malioboro-stay', 'city' => 'yogyakarta', 'name' => 'Hotel Malioboro Stay', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Malioboro No. 56, Yogyakarta',
                'description' => 'Hotel santai beberapa langkah dari Malioboro. Cocok untuk wisata budaya dan kuliner.',
                'latitude' => -7.7926, 'longitude' => 110.3658,
                'amenities' => ['Sarapan', 'WiFi cepat', 'Resepsionis 24 jam', 'Restoran', 'Balkon'],
                'rooms' => [
                    ['name' => 'Smart Room', 'description' => 'Kamar ringkas dengan semua kebutuhan dasar.', 'capacity' => 2, 'daily_price' => 550000, 'rooms' => 5],
                    ['name' => 'Deluxe Room', 'description' => 'Kamar deluxe dengan jendela besar dan meja kerja.', 'capacity' => 2, 'daily_price' => 850000, 'rooms' => 3],
                    ['name' => 'Family Room', 'description' => 'Kamar keluarga dengan dua tempat tidur dan sofa.', 'capacity' => 4, 'daily_price' => 1350000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'seturan-student-kos', 'city' => 'yogyakarta', 'name' => 'Kos Seturan Student', 'type' => PropertyType::Kos,
                'address' => 'Jl. Raya Seturan No. 9, Sleman',
                'description' => 'Kos mahasiswa dengan area belajar bersama dan akses mudah ke kampus UGM dan UNY.',
                'latitude' => -7.7712, 'longitude' => 110.3926,
                'amenities' => ['WiFi cepat', 'Dapur bersama', 'Ruang kerja', 'Laundry', 'Parkir'],
                'rooms' => [
                    ['name' => 'Kamar Putra', 'description' => 'Kamar putra dengan kasur single dan lemari.', 'capacity' => 1, 'daily_price' => 90000, 'rooms' => 5],
                    ['name' => 'Kamar Putri', 'description' => 'Kamar putri dengan kamar mandi dalam.', 'capacity' => 1, 'daily_price' => 95000, 'rooms' => 4],
                    ['name' => 'Kamar Deluxe', 'description' => 'Kamar lebih luas dengan AC dan meja kerja.', 'capacity' => 2, 'daily_price' => 140000, 'rooms' => 3],
                ],
            ],
            [
                'slug' => 'prawirotaman-heritage-hotel', 'city' => 'yogyakarta', 'name' => 'Hotel Prawirotaman Heritage', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Prawirotaman II No. 14, Yogyakarta',
                'description' => 'Hotel bergaya Jawa dengan kolam renang kecil dan sarapan tradisional.',
                'latitude' => -7.8123, 'longitude' => 110.3735,
                'amenities' => ['Kolam renang', 'Sarapan', 'WiFi cepat', 'Taman', 'Restoran'],
                'rooms' => [
                    ['name' => 'Garden Room', 'description' => 'Kamar dengan teras menghadap taman.', 'capacity' => 2, 'daily_price' => 700000, 'rooms' => 3],
                    ['name' => 'Poolside Room', 'description' => 'Kamar dekat kolam renang dengan area santai.', 'capacity' => 2, 'daily_price' => 1200000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'tunjungan-business-hotel', 'city' => 'surabaya', 'name' => 'Hotel Tunjungan Business', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Tunjungan No. 33, Surabaya',
                'description' => 'Hotel bisnis efisien di pusat kota dengan ruang kerja dan sarapan cepat.',
                'latitude' => -7.2544, 'longitude' => 112.7380,
                'amenities' => ['Sarapan', 'WiFi cepat', 'Ruang kerja', 'Parkir', 'Resepsionis 24 jam'],
                'rooms' => [
                    ['name' => 'Business Room', 'description' => 'Kamar bisnis dengan meja kerja lebar.', 'capacity' => 2, 'daily_price' => 680000, 'rooms' => 5],
                    ['name' => 'Executive Room', 'description' => 'Kamar eksekutif dengan sofa dan akses lounge.', 'capacity' => 2, 'daily_price' => 1150000, 'rooms' => 3],
                    ['name' => 'Junior Suite', 'description' => 'Suite kecil dengan ruang tamu terpisah.', 'capacity' => 3, 'daily_price' => 1800000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'wonokromo-cozy-kos', 'city' => 'surabaya', 'name' => 'Kos Wonokromo Cozy', 'type' => PropertyType::Kos,
                'address' => 'Jl. Wonokromo No. 77, Surabaya',
                'description' => 'Kos nyaman dekat kampus dan pusat perbelanjaan, dengan dapur bersama yang bersih.',
                'latitude' => -7.2906, 'longitude' => 112.7345,
                'amenities' => ['WiFi cepat', 'AC', 'Dapur bersama', 'Laundry'],
                'rooms' => [
                    ['name' => 'Standard Room', 'description' => 'Kamar standar dengan kamar mandi dalam.', 'capacity' => 1, 'daily_price' => 100000, 'rooms' => 5],
                    ['name' => 'Deluxe Room', 'description' => 'Kamar deluxe dengan meja kerja dan lemari besar.', 'capacity' => 2, 'daily_price' => 145000, 'rooms' => 3],
                ],
            ],
            [
                'slug' => 'seminyak-breeze-resort', 'city' => 'denpasar', 'name' => 'Hotel Seminyak Breeze', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Kayu Aya No. 88, Seminyak',
                'description' => 'Resort tropis dengan kolam renang utama, spa, dan akses jalan kaki ke pantai Seminyak.',
                'latitude' => -8.6833, 'longitude' => 115.1667,
                'amenities' => ['Kolam renang', 'Spa', 'Sarapan', 'Restoran', 'WiFi cepat'],
                'rooms' => [
                    ['name' => 'Garden Villa', 'description' => 'Villa dengan taman privat dan teras.', 'capacity' => 2, 'daily_price' => 2400000, 'rooms' => 3],
                    ['name' => 'Pool Villa', 'description' => 'Villa dengan kolam renang privat.', 'capacity' => 2, 'daily_price' => 3800000, 'rooms' => 2],
                    ['name' => 'Resort Suite', 'description' => 'Suite luas dengan ruang tamu dan bathtub.', 'capacity' => 3, 'daily_price' => 2900000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'canggu-digital-nomad-kos', 'city' => 'denpasar', 'name' => 'Kos Canggu Digital Nomad', 'type' => PropertyType::Kos,
                'address' => 'Jl. Pantai Berawa No. 19, Canggu',
                'description' => 'Kos untuk pekerja remote dengan co-working space, internet cepat, dan komunitas mingguan.',
                'latitude' => -8.6529, 'longitude' => 115.1376,
                'amenities' => ['Coworking', 'WiFi cepat', 'Kolam renang', 'Dapur bersama', 'Laundry'],
                'rooms' => [
                    ['name' => 'Nomad Single', 'description' => 'Kamar single dengan meja kerja dan kursi ergonomis.', 'capacity' => 1, 'daily_price' => 200000, 'rooms' => 4],
                    ['name' => 'Nomad Deluxe', 'description' => 'Kamar deluxe dengan balkon dan kamar mandi dalam.', 'capacity' => 2, 'daily_price' => 280000, 'rooms' => 3],
                    ['name' => 'Loft Room', 'description' => 'Loft dua lantai dengan area kerja dan sofa.', 'capacity' => 2, 'daily_price' => 350000, 'rooms' => 2],
                ],
            ],
            [
                'slug' => 'ijen-view-hotel', 'city' => 'malang', 'name' => 'Hotel Ijen View', 'type' => PropertyType::Hotel,
                'address' => 'Jl. Ijen No. 15, Malang',
                'description' => 'Hotel sejuk di kawasan Ijen dengan taman dan sarapan pagi.',
                'latitude' => -7.9797, 'longitude' => 112.6304,
                'amenities' => ['WiFi cepat', 'Taman', 'Sarapan', 'Parkir'],
                'rooms' => [
                    ['name' => 'Standard Room', 'description' => 'Kamar standar dengan jendela menghadap taman.', 'capacity' => 2, 'daily_price' => 480000, 'rooms' => 4],
                    ['name' => 'Deluxe Room', 'description' => 'Kamar deluxe dengan area duduk dan teras.', 'capacity' => 2, 'daily_price' => 720000, 'rooms' => 3],
                ],
            ],
            [
                'slug' => 'sukun-kos-putri', 'city' => 'malang', 'name' => 'Kos Sukun Putri', 'type' => PropertyType::Kos,
                'address' => 'Jl. Sukun No. 3, Malang',
                'description' => 'Kos putri dengan lingkungan tenang, dapur bersama, dan penjaga 24 jam.',
                'latitude' => -8.0000, 'longitude' => 112.6180,
                'amenities' => ['WiFi cepat', 'Dapur bersama', 'Keamanan 24 jam', 'Laundry'],
                'rooms' => [
                    ['name' => 'Single Room', 'description' => 'Kamar single dengan lemari dan meja belajar.', 'capacity' => 1, 'daily_price' => 85000, 'rooms' => 5],
                    ['name' => 'AC Room', 'description' => 'Kamar ber-AC dengan kamar mandi dalam.', 'capacity' => 1, 'daily_price' => 110000, 'rooms' => 3],
                ],
            ],
        ];
    }

    private function termsBody(): string
    {
        return <<<'HTML'
<h2>1. Ketentuan Umum</h2>
<p>Dengan membuat booking di KosanGo, Anda menyetujui seluruh syarat dan ketentuan yang berlaku pada versi ini.</p>
<h2>2. Pembayaran</h2>
<p>Pembayaran dilakukan melalui transfer manual dan diverifikasi oleh admin properti. Booking dikonfirmasi setelah pembayaran pertama disetujui.</p>
<h2>3. Pembatalan dan Refund</h2>
<p>Pembatalan oleh customer pada booking terkonfirmasi hanya mendapatkan refund penuh pokok sewa bila dilakukan maksimal 3 hari sejak konfirmasi dan belum check-in. Denda yang telah dibayar tidak dikembalikan.</p>
<h2>4. Keterlambatan</h2>
<p>Invoice yang melewati jatuh tempo dikenakan denda Rp 50.000 setelah toleransi 7 hari. Denda hanya berlaku pada booking terkonfirmasi.</p>
<h2>5. Perpanjangan</h2>
<p>Perpanjangan dapat diajukan mulai H-14 sebelum masa sewa berakhir dan menggunakan harga terbaru tipe kamar.</p>
HTML;
    }

    private function privacyBody(): string
    {
        return <<<'HTML'
<h2>Data yang Kami Simpan</h2>
<p>Kami menyimpan nama, email, nomor telepon, data booking, tagihan, pembayaran, dan refund untuk keperluan operasional.</p>
<h2>Penggunaan Data</h2>
<p>Data digunakan untuk memproses booking, memverifikasi pembayaran, mengirim notifikasi, dan memenuhi kewajiban hukum.</p>
<h2>Keamanan</h2>
<p>Bukti transfer dan data rekening refund disimpan secara privat dan hanya dapat diakses oleh pihak yang berwenang.</p>
<h2>Hubungi Kami</h2>
<p>Untuk pertanyaan terkait privasi, hubungi admin KosanGo melalui WhatsApp properti terkait.</p>
HTML;
    }
}
