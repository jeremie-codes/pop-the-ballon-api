<?php

namespace App\Services;

use App\Models\Campaign;

class CampaignService
{
    /**
     * Récupérer les campagnes disponibles pour le feed
     */
    public function getActiveCampaigns()
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
