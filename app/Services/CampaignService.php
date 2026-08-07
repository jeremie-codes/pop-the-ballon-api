<?php

namespace App\Services;

use App\Models\Campaign;

class CampaignService
{
    /**
     * Récupérer les campagnes disponibles pour le feed
     */
    /*public function getActiveCampaigns()
    {
        return Campaign::query()
            ->with('media')
            // campagne activée
            ->where('status', 'active')
            // début de campagne
            ->where(function ($query) {
                $query
                    ->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            // fin de campagne
            ->where(function ($query) {
                $query
                    ->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            // priorité la plus haute d'abord
            ->orderByDesc('priority')
            ->get();
    }*/

    public function getActiveCampaigns(
        ?array $cursor = null,
        int $limit = 20
    ) {
        $query = Campaign::query()
            ->with('media')
            ->where('status', 'active')

            // début de campagne
            ->where(function ($query) {
                $query
                    ->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })

            // fin de campagne
            ->where(function ($query) {
                $query
                    ->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });

        /*
        * Pagination par curseur.
        *
        * L'ordre est :
        * priority DESC
        * id DESC
        */
        if ($cursor) {
            $priority = $cursor['priority'] ?? null;
            $id = $cursor['id'] ?? null;

            if ($priority !== null && $id !== null) {
                $query->where(function ($q) use ($priority, $id) {
                    $q->where('priority', '<', $priority)
                        ->orWhere(function ($q) use ($priority, $id) {
                            $q->where('priority', '=', $priority)
                                ->where('id', '<', $id);
                        });
                });
            }
        }

        return $query
            ->orderByDesc('priority')
            ->orderByDesc('id')
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
