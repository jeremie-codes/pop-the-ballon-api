<?php

namespace App\Http\Controllers\Api;

use App\Events\LikeCreated;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Conversation;
use App\Models\MatchModel;
use App\Models\ProfileAction;
use App\Models\User;
use App\Services\ExpoNotificationService;
use Illuminate\Http\Request;
use App\Events\MatchCreated;

class InteractionController extends Controller
{
    public function like(Request $request, ExpoNotificationService $expo)
    {
        try {
            return $this->storeAction($request, 'like', $expo);
        } catch (\Throwable $e) {
            logger()->error('Like match error ', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur, ' . $e->getMessage()], 500);
        }
    }

    public function pop(Request $request, ExpoNotificationService $expo)
    {
        try {
            return $this->storeAction($request, 'pop', $expo);
        } catch (\Throwable $e) {
            logger()->error('Pop match error ', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur, ' . $e->getMessage()], 500);
        }
    }

    public function decline(Request $request)
    {
        try {
            $data = $request->validate([
                'profile_id' => ['required', 'exists:users,id'],
            ]);

            $actor = $request->user('sanctum');
            $targetId = (int) $data['profile_id'];

            // On supprime simplement le like reçu
            ProfileAction::query()
                ->where('actor_id', $targetId)
                ->where('target_id', $actor->id)
                ->where('type', 'like')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande refusée.',
            ]);
        } catch (\Throwable $e) {
            logger()->error('Decline match error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur interne.',
            ], 500);
        }
    }

    public function pendingLikesCount(Request $request)
    {
        $count = ProfileAction::query()
            ->where('target_id', $request->user('sanctum')->id)
            ->where('type', 'like')
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'userId' => $request->user('sanctum')->id,
            'count' => $count
        ]);
    }

    private function storeAction(Request $request, string $type, ExpoNotificationService $expo)
    {
        try {
            $data = $request->validate([
                'profile_id' => ['required', 'exists:users,id'],
            ]);

            $actor = $request->user('sanctum');
            $targetId = (int) $data['profile_id']; // A remettre pour la prod
            //$targetId = (int) $request->user('sanctum')->id; // A retirer pour la prod

            // Empêche les actions sur soi-même (utile pour les tests, à retirer en prod)
            if ($actor->id === $targetId) {
                return response()->json([
                    'message' => 'Action impossible sur votre propre profil.'
                ], 422);
            }

            $target = User::query()->with('devices')->find($targetId);

            // Enregistre ou met à jour l'action
            $profileAction = ProfileAction::query()->updateOrCreate(
                [
                    'actor_id' => $actor->id,
                    'target_id' => $targetId,
                ],
                [
                    'type' => $type,
                    'status' => 'pending',
                ]
            );

            // Vérifie si la cible a liké l'acteur
            $targetLikedActor = ProfileAction::query()
                ->where('actor_id', $targetId)
                ->where('target_id', $actor->id)
                ->where('type', 'like')
                ->exists();

            // Notification
            if ($type === 'like') {

                if ($targetLikedActor) {

                    AppNotification::createAndBroadcast([
                        'user_id' => $targetId,
                        'title' => '❤️ Demande de match confirmé !',
                        'message' => $actor->displayName() . ' a accepté votre demande de match.',
                        'kind' => 'match',
                        'profile_id' => $actor->id,
                    ]);

                    foreach ($target->devices as $device) {
                        $expo->send(
                            $device->expo_token,
                            '🎈PopTheBallon - Nouvelle notification',
                            $actor->displayName() . ' a accepté votre demande de match.',
                            [
                                'type' => 'match',
                                'user_id' => $actor->id,
                                'url' => '/(main)/matches',
                            ]
                        );
                    }
                } else {

                    AppNotification::createAndBroadcast([
                        'user_id' => $targetId,
                        'title' => 'Nouvelle demande de match',
                        'message' => $actor->displayName() . ' aime votre profil, cliquez pour voir.',
                        'kind' => 'like',
                        'profile_id' => $actor->id,
                    ]);

                    LikeCreated::dispatch(
                        $target,
                        $profileAction
                    );

                    foreach ($target->devices as $device) {
                        $expo->send(
                            $device->expo_token,
                            '🎈PopTheBallon - Nouvelle notification',
                            $actor->displayName() . ' aime votre profil, cliquez pour voir.',
                            [
                                'type' => 'like',
                                'user_id' => $actor->id,
                                'url' => '/(main)/likes',
                            ]
                        );
                    }
                }
            }


            // Match uniquement si les deux ont liké
            $actorLikedTarget = ProfileAction::query()
                ->where('actor_id', $actor->id)
                ->where('target_id', $targetId)
                ->where('type', 'like')
                ->exists();

            $matched = $actorLikedTarget && $targetLikedActor;

            if ($matched) {
                // Les deux likes ne sont plus en attente
                ProfileAction::query()
                    ->where(function ($q) use ($actor, $targetId) {
                        $q->where('actor_id', $actor->id)
                        ->where('target_id', $targetId);
                    })
                    ->orWhere(function ($q) use ($actor, $targetId) {
                        $q->where('actor_id', $targetId)
                        ->where('target_id', $actor->id);
                    })
                    ->where('type', 'like')
                    ->update([
                        'status' => 'accepted',
                    ]);

                [$one, $two] = collect([
                    $actor->id,
                    $targetId,
                ])->sort()->values()->all();

                [$one, $two] = collect([
                    $actor->id,
                    $targetId,
                ])->sort()->values()->all();

                $match = MatchModel::query()->firstOrCreate(
                    [
                        'user_one_id' => $one,
                        'user_two_id' => $two,
                    ],
                    [
                        'matched_at' => now(),
                    ]
                );

                $conversation = Conversation::query()->firstOrCreate(
                    [
                        'user_one_id' => $one,
                        'user_two_id' => $two,
                    ],
                    [
                        'match_id' => $match->id,
                    ]
                );

                // Évite de diffuser MatchCreated plusieurs fois
                // si le match existait déjà.
                if ($match->wasRecentlyCreated) {
                    $actor->loadMissing('photos');
                    $target->loadMissing('photos');

                    // Pour A, le nouveau match est B
                    MatchCreated::dispatch(
                        $actor->id,
                        $target,
                        $conversation,
                    );

                    // Pour B, le nouveau match est A
                    MatchCreated::dispatch(
                        $target->id,
                        $actor,
                        $conversation,
                    );
                }
            }

            return response()->json([
                'success' => true,
                'matched' => $matched,
                'message' => $matched
                    ? 'Match confirmé.'
                    : 'Action enregistrée.',
            ]);
        } catch (\Throwable $e) {
            logger()->error('storeAction failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
