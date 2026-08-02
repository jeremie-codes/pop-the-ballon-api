<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_type' => $this->faker->randomElement([
                'admin',
                'partner',
                'user'
            ]),

            'owner_id' => User::inRandomOrder()->value('id'),

            'title' => $this->faker->sentence(3),

            'description' => $this->faker->paragraph(),

            'campaign_type' => $this->faker->randomElement([
                'feature',
                'commercial',
                'sponsored'
            ]),

            'media_type' => $this->faker->randomElement([
                'image',
                'carousel',
                'video'
            ]),

            'status' => 'active',

            'button_text' => $this->faker->randomElement([
                'Découvrir',
                'Acheter',
                'Réserver'
            ]),

            'target_type' => 'url',

            'target_value' => 'https://example.com',

            'priority' => rand(1, 10),

            'budget' => rand(100, 1000),

            'price' => rand(5, 50),

            'views_count' => 0,

            'clicks_count' => 0,

            'created_by' => User::inRandomOrder()->value('id'),

            'start_at' => now(),

            'end_at' => now()->addDays(rand(10, 60)),
        ];
    }
}
