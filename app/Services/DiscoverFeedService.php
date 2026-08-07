<?php
/*
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

}*/

namespace App\Services;

use App\Models\User;

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

    /**
     * Construire une page du feed.
     *
     * Structure :
     *
     * 2 profils
     * 1 campagne
     * 2 profils
     * 1 campagne
     * ...
     */
    public function build(
        ?User $user = null,
        int $limit = 12,
        ?string $cursor = null
    ): array {
        $decodedCursor = $this->decodeCursor($cursor);

        $profileCursor = $decodedCursor['profile'] ?? null;
        $campaignCursor = $decodedCursor['campaign'] ?? null;

        /*
         * On demande suffisamment d'éléments aux deux sources.
         *
         * On demande "limit" profils et campagnes pour être certain
         * de pouvoir remplir la page même si une des deux sources
         * arrive à épuisement.
         */
        $profiles = $this->profileService->getProfiles(
            $user,
            $profileCursor,
            $limit
        );

        $campaigns = $this->campaignService->getActiveCampaigns(
            $campaignCursor,
            $limit
        );

        $result = $this->mergeFeed(
            $profiles,
            $campaigns,
            $limit
        );

        /*
         * Les derniers éléments réellement consommés par le merge
         * deviennent les nouveaux curseurs.
         */
        $nextCursor = [
            'profile' => $result['last_profile']
                ? [
                    'created_at' => $result['last_profile']->created_at?->toISOString(),
                    'id' => $result['last_profile']->id,
                ]
                : $profileCursor,

            'campaign' => $result['last_campaign']
                ? [
                    'priority' => $result['last_campaign']->priority,
                    'id' => $result['last_campaign']->id,
                ]
                : $campaignCursor,
        ];

        $hasMore = (
            $result['profile_index'] < $profiles->count()
            || $result['campaign_index'] < $campaigns->count()
        );

        /*
         * Si les deux sources sont épuisées, il n'y a plus rien
         * à charger.
         */
        if (!$hasMore) {
            $encodedNextCursor = null;
        } else {
            $encodedNextCursor = $this->encodeCursor($nextCursor);
        }

        return [
            'data' => $result['feed'],
            'next_cursor' => $encodedNextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Mélange les profils et campagnes :
     *
     * 2 profils → 1 campagne
     *
     * et s'arrête exactement au nombre demandé.
     */
    private function mergeFeed(
        $profiles,
        $campaigns,
        int $limit
    ): array {
        $feed = [];

        $profileIndex = 0;
        $campaignIndex = 0;

        $lastProfile = null;
        $lastCampaign = null;

        /*while (
            count($feed) < $limit &&
            (
                $profileIndex < $profiles->count()
                || $campaignIndex < $campaigns->count()
            )
        )*/

        while (
            count($feed) < $limit &&
            $profileIndex < $profiles->count()
        )
        {
            /*
             * 2 profils
             */
            for ($i = 0; $i < 2; $i++) {
                if (
                    count($feed) >= $limit ||
                    $profileIndex >= $profiles->count()
                ) {
                    break;
                }

                $profile = $profiles[$profileIndex];

                $feed[] = [
                    'type' => 'profile',
                    'data' => $profile,
                ];

                $lastProfile = $profile;
                $profileIndex++;
            }

            /*
             * 1 campagne
             */
            if (
                count($feed) < $limit &&
                $campaignIndex < $campaigns->count()
            ) {
                $campaign = $campaigns[$campaignIndex];

                $feed[] = [
                    'type' => 'campaign',
                    'data' => $campaign,
                ];

                $lastCampaign = $campaign;
                $campaignIndex++;
            }
        }

        return [
            'feed' => $feed,
            'profile_index' => $profileIndex,
            'campaign_index' => $campaignIndex,
            'last_profile' => $lastProfile,
            'last_campaign' => $lastCampaign,
        ];
    }

    /**
     * Encoder le curseur en base64 URL-safe.
     */
    private function encodeCursor(array $cursor): string
    {
        return rtrim(
            strtr(
                base64_encode(json_encode($cursor)),
                '+/',
                '-_'
            ),
            '='
        );
    }

    /**
     * Décoder le curseur.
     */
    private function decodeCursor(?string $cursor): ?array
    {
        if (!$cursor) {
            return null;
        }

        try {
            $padding = strlen($cursor) % 4;

            if ($padding > 0) {
                $cursor .= str_repeat('=', 4 - $padding);
            }

            $decoded = base64_decode(
                strtr($cursor, '-_', '+/'),
                true
            );

            if ($decoded === false) {
                return null;
            }

            $data = json_decode($decoded, true);

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
