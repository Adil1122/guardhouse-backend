<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::withoutEvents(function () {
            User::create([
                'role' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'ahsanhanif99@gmail.com',
                'password' => Hash::make('redhat123!'),
                'api_token' => Str::random(60),
                'status' => 1,
                'email_verified_at' => now(),
            ]);
        });
    }
}
