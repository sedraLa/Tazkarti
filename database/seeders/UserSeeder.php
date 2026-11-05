<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        // Admin User
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@tazkarti.com',
            'password' => $password,
            'role' => UserRole::ADMIN,
            'phone' => '+201000000001',
            'email_verified_at' => now(),
        ]);

        // Organizer 1
        $organizer1User = User::create([
            'name' => 'Red Sea Events Lead',
            'email' => 'organizer1@tazkarti.com',
            'password' => $password,
            'role' => UserRole::ORGANIZER,
            'phone' => '+201000000002',
            'email_verified_at' => now(),
        ]);

        $organizer1 = Organizer::create([
            'user_id' => $organizer1User->id,
            'name' => 'Red Sea Productions',
            'description' => 'Premier event management and festival production organizer.',
            'phone' => '+201000000002',
        ]);

        $organizer1->images()->create([
            'path' => 'organizers/red_sea_logo.png',
        ]);

        // Organizer 2
        $organizer2User = User::create([
            'name' => 'Nile Cultural Foundation Lead',
            'email' => 'organizer2@tazkarti.com',
            'password' => $password,
            'role' => UserRole::ORGANIZER,
            'phone' => '+201000000003',
            'email_verified_at' => now(),
        ]);

        $organizer2 = Organizer::create([
            'user_id' => $organizer2User->id,
            'name' => 'Nile Cultural Foundation',
            'description' => 'Dedicated to organizing art exhibitions, theatre performances, and symphonies.',
            'phone' => '+201000000003',
        ]);

        $organizer2->images()->create([
            'path' => 'organizers/nile_cultural_logo.png',
        ]);

        // Standard Customer Users
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "Customer {$i}",
                'email' => "customer{$i}@tazkarti.com",
                'password' => $password,
                'role' => UserRole::CUSTOMER,
                'phone' => sprintf('+20101111%04d', $i),
                'email_verified_at' => now(),
            ]);
        }
    }
}
