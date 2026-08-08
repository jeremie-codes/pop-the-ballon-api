<?php

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
     * Construire une page du Feed.
     *
     * Structure :
     *
     * P P C
     * P P C
     * P P C
     *
     * Les profils utilisent une pagination par cursor.
     *
     * Les campagnes sont sélectionnées aléatoirement
     * et pondérées selon leur diffusion.
     */
    public function build(
        ?User $user = null,
        int $limit = 12,
        ?string $cursor = null
    ): array {

        $decodedCursor = $this->decodeCursor($cursor);

        $profileCursor = $decodedCursor['profile'] ?? null;

        $seenCampaignIds = $decodedCursor['campaign_seen'] ?? [];

        if (!is_array($seenCampaignIds)) {
            $seenCampaignIds = [];
        }

        /*
        * Pour respecter :
        *
        * P P C
        * P P C
        * P P C
        * P P C
        *
        * 12 éléments nécessitent 8 profils.
        */
        $profileLimit = (int) ceil($limit * 2 / 3);

        /*
        * +1 pour savoir s'il existe encore
        * des profils après cette page.
        */
        $profiles = $this->profileService->getProfiles(
            $user,
            $profileCursor,
            $profileLimit + 1
        );

        $hasMoreProfiles = $profiles->count() > $profileLimit;

        /*
        * Le profil supplémentaire sert uniquement
        * à savoir s'il y a encore des profils.
        */
        $profilesForFeed = $profiles->take($profileLimit);

        /*$result = $this->mergeFeed(
            $profilesForFeed,
            $seenCampaignIds,
            $limit
        );*/

        $campaigns = $this->campaignService->getActiveCampaigns(
            $seenCampaignIds,
            20
        );

        $result = $this->mergeFeed(
            $profilesForFeed,
            $campaigns,
            $seenCampaignIds,
            $limit
        );

        $nextProfileCursor = $result['last_profile']
            ? [
                'created_at' => $result['last_profile']
                    ->created_at
                    ?->toISOString(),

                'id' => $result['last_profile']->id,
            ]
            : $profileCursor;

        $hasMore = $hasMoreProfiles;

        if (!$hasMore) {

            $encodedNextCursor = null;
        } else {

            $nextCursor = [
                'profile' => $nextProfileCursor,

                'campaign_seen' => $result['seen_campaign_ids'],
            ];

            $encodedNextCursor = $this->encodeCursor(
                $nextCursor
            );
        }

        return [
            'data' => $result['feed'],
            'next_cursor' => $encodedNextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Mélange :
     *
     * P P C
     * P P C
     * P P C
     *
     * Les campagnes sont choisies dynamiquement.
     */
    /*private function mergeFeed(
        $profiles,
        array $seenCampaignIds,
        int $limit
    ): array {*/

    private function mergeFeed(
        $profiles,
        $campaigns,
        array $seenCampaignIds,
        int $limit
    ): array {

        $feed = [];

        $profileIndex = 0;
        $lastProfile = null;

        while (
            count($feed) < $limit &&
            $profileIndex < $profiles->count()
        ) {

            /*
         * ==========================
         * 2 PROFILS
         * ==========================
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
            * ==========================
            * 1 CAMPAGNE
            * ==========================
            */
            if (count($feed) < $limit && $campaigns->isNotEmpty()) {

                /*
             * On sélectionne une campagne
             * parmi celles déjà chargées en mémoire.
             */
                $selectedCampaign = $this->selectWeightedCampaign(
                    $campaigns
                );

                if ($selectedCampaign) {

                    $feed[] = [
                        'type' => 'campaign',
                        'data' => $selectedCampaign,
                    ];

                    /*
                    * On retire la campagne sélectionnée
                    * de la collection locale.
                    *
                    * Cela évite qu'elle soit sélectionnée
                    * deux fois dans le même cycle/page.
                    */
                    $campaigns = $campaigns
                        ->reject(
                            fn($campaign) =>
                            $campaign->id === $selectedCampaign->id
                        )
                        ->values();

                    $seenCampaignIds[] = $selectedCampaign->id;
                }
            }
        }

        return [
            'feed' => $feed,

            'profile_index' => $profileIndex,

            'last_profile' => $lastProfile,

            'seen_campaign_ids' => array_values(
                array_unique($seenCampaignIds)
            ),
        ];
    }

    private function selectWeightedCampaign($campaigns)
    {
        if ($campaigns->isEmpty()) {
            return null;
        }

        $totalWeight = 0;

        foreach ($campaigns as $campaign) {
            $totalWeight += $this->calculateCampaignWeight($campaign);
        }

        if ($totalWeight <= 0) {
            return $campaigns->random();
        }

        $random = mt_rand(1, $totalWeight);

        $currentWeight = 0;

        foreach ($campaigns as $campaign) {

            $currentWeight += $this->calculateCampaignWeight(
                $campaign
            );

            if ($random <= $currentWeight) {
                return $campaign;
            }
        }

        return $campaigns->last();
    }

    private function calculateCampaignWeight($campaign): int
    {
        $views = (int) ($campaign->views_count ?? 0);

        $priority = max(
            1,
            (int) ($campaign->priority ?? 1)
        );

        $deliveryWeight = max(
            1,
            (int) floor(1000 / ($views + 1))
        );

        return $deliveryWeight * $priority;
    }

    /**
     * Encoder le cursor en Base64 URL-safe.
     */
    private function encodeCursor(array $cursor): string
    {
        return rtrim(
            strtr(
                base64_encode(
                    json_encode($cursor)
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }

    /**
     * Décoder le cursor.
     */
    private function decodeCursor(?string $cursor): ?array
    {
        if (!$cursor) {
            return null;
        }

        try {

            $padding = strlen($cursor) % 4;

            if ($padding > 0) {
                $cursor .= str_repeat(
                    '=',
                    4 - $padding
                );
            }

            $decoded = base64_decode(
                strtr(
                    $cursor,
                    '-_',
                    '+/'
                ),
                true
            );

            if ($decoded === false) {
                return null;
            }

            $data = json_decode(
                $decoded,
                true
            );

            return is_array($data)
                ? $data
                : null;
        } catch (\Throwable $e) {

            return null;
        }
    }
}
