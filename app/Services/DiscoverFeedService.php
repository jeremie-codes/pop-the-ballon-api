<?php

namespace App\Services;

use App\Models\User;
use App\Services\DiscoverProfileService;
use App\Services\CampaignService;

class DiscoverFeedService
{

    protected CampaignService $campaignService;
    protected DiscoverProfileService $profileService;


    public function __construct(
        CampaignService $campaignService,
        DiscoverProfileService $profileService
    ) {
        $this->campaignService = $campaignService;
        $this->profileService = $profileService;
    }


    public function build(?User $user = null)
    {
        $profiles = $this->profileService->getProfiles($user);
        $campaigns = $this->campaignService->getActiveCampaigns();
        return $this->mergeFeed($profiles,$campaigns);
    }

    private function mergeFeed($profiles, $campaigns)
    {
        $feed = [];

        $profileIndex = 0;
        $campaignIndex = 0;

        while (
            $profileIndex < $profiles->count() || $campaignIndex < $campaigns->count()
        ) {
            // 2 profils
            for ($i = 0; $i < 2; $i++) {
                if ($profileIndex < $profiles->count()) {
                    $feed[] = [
                        'type' => 'profile',
                        'data' => $profiles[$profileIndex]
                    ];
                    $profileIndex++;
                }
            }

            // 1 campagne
            if ($campaignIndex < $campaigns->count()) {
                $feed[] = [
                    'type' => 'campaign',
                    'data' => $campaigns[$campaignIndex]
                ];
                $campaignIndex++;
            }
        }

        return $feed;
    }

}
