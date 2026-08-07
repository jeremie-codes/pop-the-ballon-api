<?php
/*

namespace App\Services;

use App\Models\User;
use App\Models\MatchModel;
use App\Models\ProfileAction;

class DiscoverProfileService
{

    public function getProfiles(?User $user = null)
    {

        $query = User::query()
            ->with(['photos', 'interests'])
            ->where('is_visible', true)
            ->where('is_staff', false)
            ->where('deleted_at', null)
            ->where('role', '!=', 'admin')
            ->where('role', '!=', 'support')
            ->latest();

        if ($user) {

            // Exclure son propre profil
            $query->whereKeyNot($user->id);

            // Profils déjà traités
            $handledProfiles = ProfileAction::query()
                ->where('actor_id', $user->id)
                ->whereIn('type', ['like', 'pop'])
                ->pluck('target_id');

            if ($handledProfiles->isNotEmpty()) {
                $query->whereNotIn(
                    'id',
                    $handledProfiles
                );
            }

            // Exclure les matchs existants
            $matchedIds = MatchModel::query()
                ->where(function ($q) use ($user) {

                    $q->where(
                        'user_one_id',
                        $user->id
                    )
                    ->orWhere(
                        'user_two_id',
                        $user->id
                    );

                })
                ->get()
                ->flatMap(function ($match) use ($user) {
                    return [
                        $match->user_one_id == $user->id
                            ? $match->user_two_id
                            : $match->user_one_id
                    ];
                });

            if ($matchedIds->isNotEmpty()) {
                $query->whereNotIn(
                    'id',
                    $matchedIds
                );
            }
        }

        return $query->get();

    }

}
*/

namespace App\Services;

use App\Models\User;
use App\Models\MatchModel;
use App\Models\ProfileAction;
use Illuminate\Support\Collection;

class DiscoverProfileService
{
    public function getProfiles(
        ?User $user = null,
        ?array $cursor = null,
        int $limit = 20
    ): Collection {
        $query = User::query()
            ->with(['photos', 'interests'])
            ->where('is_visible', true)
            ->where('is_staff', false)
            ->whereNull('deleted_at')
            ->where('role', '!=', 'admin')
            ->where('role', '!=', 'support');

        if ($user) {
            // Exclure son propre profil
            $query->whereKeyNot($user->id);

            // Profils déjà traités
            $handledProfiles = ProfileAction::query()
                ->where('actor_id', $user->id)
                ->whereIn('type', ['like', 'pop'])
                ->pluck('target_id');

            if ($handledProfiles->isNotEmpty()) {
                $query->whereNotIn('id', $handledProfiles);
            }

            // Exclure les matchs existants
            $matchedIds = MatchModel::query()
                ->where(function ($q) use ($user) {
                    $q->where('user_one_id', $user->id)
                        ->orWhere('user_two_id', $user->id);
                })
                ->get()
                ->map(function ($match) use ($user) {
                    return $match->user_one_id == $user->id
                        ? $match->user_two_id
                        : $match->user_one_id;
                });

            if ($matchedIds->isNotEmpty()) {
                $query->whereNotIn('id', $matchedIds);
            }
        }

        /*
         * Pagination par curseur.
         *
         * L'ordre est :
         * created_at DESC
         * id DESC
         *
         * Le id permet de départager deux profils
         * ayant exactement la même date.
         */
        if ($cursor) {
            $createdAt = $cursor['created_at'] ?? null;
            $id = $cursor['id'] ?? null;

            if ($createdAt && $id) {
                $query->where(function ($q) use ($createdAt, $id) {
                    $q->where('created_at', '<', $createdAt)
                        ->orWhere(function ($q) use ($createdAt, $id) {
                            $q->where('created_at', '=', $createdAt)
                                ->where('id', '<', $id);
                        });
                });
            }
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
