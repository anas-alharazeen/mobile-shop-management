<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@fanana-phone.local'],
            [
                'name' => 'مالك فنانة فون',
                'email' => 'owner@fanana-phone.local',
                'password' => Hash::make('Fanana@123456'),
                'email_verified_at' => now(),
            ]
        );
    }
}
