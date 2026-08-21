<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // HR
        User::updateOrCreate(
            
            ['email' => 'budi@gmail.com'],
            [
                'id' => 1,
                'name' => 'Budi',
                'password' => Hash::make('budi123'),
                'role' => 'hr',
                'is_active' => true,
                'manager_id' => null,
            ]
        );

        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'ibnu@gmail.com'],
            [
                'name' => 'Ibnu',
                'password' => Hash::make('ibnu123'),
                'role' => 'manager',
                'is_active' => true,
                'manager_id' => null,
            ]
        );

        // Employee A
        User::updateOrCreate(
            ['email' => 'rifan@gmail.com'],
            [
                'name' => 'Rivan',
                'password' => Hash::make('rifan123'),
                'role' => 'employee',
                'is_active' => true,
                'manager_id' => $manager->id,
            ]
        );

        // Employee B
        User::updateOrCreate(
            ['email' => 'davez@gmail.com'],
            [
                'name' => 'Davez',
                'password' => Hash::make('davez123'),
                'role' => 'employee',
                'is_active' => true,
                'manager_id' => $manager->id,
            ]
        );
    }
}