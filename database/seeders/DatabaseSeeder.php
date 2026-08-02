<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*User::updateOrCreate(
            ['email' => 'jeremie@test.com'],
            [
                'first_name' => 'Jeremie',
                'last_name' => 'Mianda',
                'username' => 'jeremie',
                'phone' => '243810000001',
                'birth_date' => '1998-01-15',
                'gender' => 'male',
                'city' => 'Kinshasa',
                'country' => 'RDC',
                'intention' => 'serious',
                'bio' => 'Compte de test 1',
                'verified' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'sarah@test.com'],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'username' => 'sarah',
                'phone' => '243810000002',
                'birth_date' => '1999-05-20',
                'gender' => 'female',
                'city' => 'Kinshasa',
                'country' => 'RDC',
                'intention' => 'friendship',
                'bio' => 'Compte de test 2',
                'verified' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );*/

    }
}
