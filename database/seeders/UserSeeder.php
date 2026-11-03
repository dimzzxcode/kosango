<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed one demo account for each role. All accounts use the password "password".
     */
    public function run(): void
    {
        foreach ($this->accounts() as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, email: string, phone: string, role: UserRole}>
     */
    private function accounts(): array
    {
        return [
            [
                'name' => 'Superadmin KosanGo',
                'email' => 'superadmin@kosango.test',
                'phone' => '081100000001',
                'role' => UserRole::Superadmin,
            ],
            [
                'name' => 'Admin Properti',
                'email' => 'admin@kosango.test',
                'phone' => '081100000002',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '081234567890',
                'role' => UserRole::Customer,
            ],
        ];
    }
}
