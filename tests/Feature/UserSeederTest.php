<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('it seeds one verified account for each role', function () {
    $this->seed(UserSeeder::class);

    $accounts = [
        'superadmin@kosango.test' => UserRole::Superadmin,
        'admin@kosango.test' => UserRole::Admin,
        'test@example.com' => UserRole::Customer,
    ];

    foreach ($accounts as $email => $role) {
        $user = User::query()->where('email', $email)->first();

        expect($user)->not->toBeNull();
        expect($user?->role)->toBe($role);
        expect($user?->email_verified_at)->not->toBeNull();
        expect(Hash::check('password', $user?->password ?? ''))->toBeTrue();
    }

    expect(User::query()->count())->toBe(3);
});

test('seeding accounts repeatedly does not duplicate them', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::query()->count())->toBe(3);
});
