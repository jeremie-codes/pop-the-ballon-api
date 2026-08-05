<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::truncate();

        $countries = json_decode(
            file_get_contents(database_path('data/countries.json')),
            true
        );

        foreach ($countries as $country) {
            Country::create([
                'name' => $country['name'],
                'iso' => $country['iso'],
                'phone_code' => $country['phone_code'],
                'flag' => $country['flag'],
                'is_active' => true,
            ]);
        }
    }
}
