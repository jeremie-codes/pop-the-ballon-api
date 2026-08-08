<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Collection;

class CampaignService
{
    /**
     * Récupère les campagnes actives et les prépare
     * pour une sélection aléatoire pondérée.
     *
     * Les campagnes déjà utilisées dans le cycle sont exclues.
     */
    public function getActiveCampaigns(
        array $excludedIds = [],
        int $limit = 20
    ): Collection {

        $query = Campaign::query()
            ->with('media')
            ->where('status', 'active')

            ->where(function ($query) {
                $query
                    ->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })

            ->where(function ($query) {
                $query
                    ->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });

        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        return $query
            ->limit($limit)
            ->get();
    }

    public function findActiveCampaign($id)
    {
        return Campaign::query()
            ->with('media')
            ->where('id', $id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query
                    ->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($query) {
                $query
                    ->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->first();
    }
}
