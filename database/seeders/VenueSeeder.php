<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Cairo International Stadium',
                'city' => 'Cairo',
                'address' => 'Nasr City, Cairo',
                'description' => 'Olympic-standard multi-use stadium suitable for massive concerts and sports.',
                'image' => 'venues/cairo_stadium.jpg',
            ],
            [
                'name' => 'Alexandria Opera House (Sayed Darwish Theatre)',
                'city' => 'Alexandria',
                'address' => '22 Fouad Street, Raml Station, Alexandria',
                'description' => 'Historic cultural opera house hosting concerts, ballets, and conferences.',
                'image' => 'venues/alex_opera.jpg',
            ],
            [
                'name' => 'Giza Plateau Open Air Arena',
                'city' => 'Giza',
                'address' => 'Pyramids Complex, Al Haram, Giza',
                'description' => 'Spectacular outdoor event space with direct views of the Great Pyramids.',
                'image' => 'venues/giza_pyramids.jpg',
            ],
            [
                'name' => 'El Gouna Festival Plaza',
                'city' => 'Hurghada',
                'address' => 'El Gouna, Hurghada, Red Sea Governorate',
                'description' => 'Seaside open plaza designed for international film and music festivals.',
                'image' => 'venues/elgouna_plaza.jpg',
            ],
        ];

        foreach ($venues as $venueData) {
            $imagePath = $venueData['image'];
            unset($venueData['image']);

            $venue = Venue::create($venueData);
            $venue->images()->create([
                'path' => $imagePath,
            ]);
        }
    }
}
