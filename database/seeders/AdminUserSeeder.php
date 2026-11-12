<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@gmail.com';
        $password = '123456789012';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );

        if ($user->role !== 'admin' || !Hash::check($password, $user->password)) {
            throw new RuntimeException(
                "The seeded admin email already exists with a different role or password: {$email}"
            );
        }
    }
}
