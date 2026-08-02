<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\CampaignMedia;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
    */

    public function run(): void
    {
        Campaign::factory()
            ->count(20)
            ->create()
            ->each(function ($campaign) {

                CampaignMedia::create([
                    'campaign_id' => $campaign->id,
                    'type' => 'image',
                    'path' => 'campaigns/default.jpg',
                    'order' => 1,
                ]);

            });
    }
}
