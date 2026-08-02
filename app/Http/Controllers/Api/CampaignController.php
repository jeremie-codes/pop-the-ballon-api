<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignView;
use App\Models\CampaignClick;
use Illuminate\Http\Request;

class CampaignController extends Controller
{

    public function index()
    {
        $campaigns = Campaign::query()
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
            })
            ->orderByDesc('priority')
            ->get();

        return CampaignResource::collection($campaigns);
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('media');
        return new CampaignResource($campaign);
    }

    private function alreadyTracked(string $modelClass, Campaign $campaign, Request $request): bool
    {
        $query = $modelClass::where('campaign_id', $campaign->id);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        } else {
            $query->where('visitor_id', $request->visitor_id);
        }

        return $query
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }

    public function view(Request $request, Campaign $campaign)
    {
        try {
            if (! $this->alreadyTracked(CampaignView::class, $campaign, $request)) {
                CampaignView::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => auth()->id(),
                    'visitor_id' => $request->header('X-Visitor-Id'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                $campaign->increment('views_count');
            }

            return response()->json([
                'success' => true,
            ]);

        } catch (\Throwable $e) {

            logger()->error('Campaign view failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur.'
            ], 500);
        }
    }

    public function click(Request $request, Campaign $campaign)
    {
        try {

            if (! $this->alreadyTracked(CampaignClick::class, $campaign, $request)) {
                CampaignClick::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => auth()->id(),
                    'visitor_id' => $request->header('X-Visitor-Id'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $campaign->increment('clicks_count');
            }

            return response()->json([
                'message' => 'Clic enregistré',
                'target' => [
                    'type' => $campaign->target_type,
                    'value' => $campaign->target_value,
                ],
            ]);

        } catch (\Throwable $e) {

            logger()->error('Campaign click failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }
}
