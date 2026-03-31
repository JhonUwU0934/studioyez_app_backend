<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'adrian@example.com'],
            [
                'name' => 'Adrian',
                'password' => Hash::make('1234567890'),
                'rol' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'jhon@example.com'],
            [
                'name' => 'Jhon',
                'password' => Hash::make('12345678'),
                'rol' => 'vendedor',
            ]
        );
    }
}
