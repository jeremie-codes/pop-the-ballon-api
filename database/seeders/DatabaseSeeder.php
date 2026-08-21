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
            ['email' => 'admin@pop.cloud'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'admin',
                'role' => 'admin',
                'city' => 'Kinshasa',
                'country' => 'RDC',
                'bio' => 'Compte administrateur',
                'verified' => true,
                'is_staff' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('@admin123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'support@pop.cloud'],
            [
                'first_name' => 'Equipe',
                'last_name' => 'PopTheBallon',
                'username' => 'support',
                'role' => 'support',
                'city' => 'Kinshasa',
                'country' => 'RDC',
                'bio' => 'Compte support technique',
                'verified' => true,
                'is_staff' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('@admin123'),
            ]
        );*/

        $this->call([
            PopChoiceSeeder::class,
        ]);

    }
}
