<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\ProfileAction;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\DiscoverFeedResource;
use App\Models\MessageCredit;
use App\Services\DiscoverFeedService;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function discoverFeed(Request $request)
    {
        $feed = app(DiscoverFeedService::class)->build($request->user('sanctum'));
        return response()->json(
            DiscoverFeedResource::collection($feed)->resolve()
        );
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user('sanctum');

            if (! $user) {
                return response()->json([
                    'message' => 'Non authentifié.'
                ], 401);
            }

            if ($user->deleted_at) {

                // Supprime tous les tokens restants
                $user->tokens()->delete();

                return response()->json([
                    'code' => 'account_deleted',
                    'message' => 'Ce compte a été supprimé.'
                ], 403);
            }

            return response()->json(
                $this->userPayload(
                    $user->load(['photos', 'interests'])
                )
            );
        } catch (\Throwable $e) {
            logger()->error('ProfileController.me error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération du profil.'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = $request->user('sanctum');

            $data = $request->validate([
                'first_name' => ['sometimes', 'required', 'string', 'max:255'],
                'last_name' => ['sometimes', 'required', 'string', 'max:255'],
                'birth_date' => ['sometimes', 'nullable', 'date'],
                'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
                'phone' => ['sometimes', 'nullable', 'string', 'max:15'],
                'email' => ['sometimes', 'nullable', 'email', 'max:80'],
                'city' => ['sometimes', 'nullable', 'string', 'max:120'],
                'country' => ['sometimes', 'nullable', 'string', 'max:120'],
                'intention' => ['sometimes', 'nullable', 'string', 'max:255'],
                'bio' => ['sometimes', 'nullable', 'string'],
                'interests' => ['sometimes', 'array'],
                'interests.*' => ['string', 'max:80'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Vérification de l'email
            |--------------------------------------------------------------------------
            */
            if (array_key_exists('email', $data) && !empty($data['email'])) {

                $emailExists = User::where('email', $data['email'])
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($emailExists) {
                    return response()->json([
                        'message' => 'Cette adresse email est déjà utilisée par un autre compte.'
                    ], 422);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Vérification du téléphone
            |--------------------------------------------------------------------------
            */
            if (array_key_exists('phone', $data) && !empty($data['phone'])) {

                $phoneExists = User::where('phone', $data['phone'])
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($phoneExists) {
                    return response()->json([
                        'message' => 'Ce numéro de téléphone est déjà utilisé par un autre compte.'
                    ], 422);
                }
            }

            $user = DB::transaction(function () use ($data, $user) {

                $interests = $data['interests'] ?? null;

                unset($data['interests']);

                $user->forceFill($data)->save();

                if (is_array($interests)) {
                    $user->interests()->delete();

                    foreach ($interests as $interest) {
                        $user->interests()->create([
                            'name' => $interest
                        ]);
                    }
                }

                return $user->load([
                    'photos',
                    'interests'
                ]);
            });

            return response()->json(
                $this->userPayload($user)
            );
        } catch (\Throwable $e) {

            logger()->error('ProfileController.update error', [
                'user_id' => $request->user('sanctum')?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du profil.'
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $user = $request->user('sanctum');

            $data = $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed']
            ]);

            /*
            |--------------------------------------------------------------------------
            | Empêcher de remettre le même mot de passe
            |--------------------------------------------------------------------------
            */
            if (Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'message' => 'Le nouveau mot de passe doit être différent de l’ancien.'
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Mise à jour
            |--------------------------------------------------------------------------
            */
            $user->update([
                'password' => Hash::make($data['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe mis à jours avec succès.'
            ]);
        } catch (\Throwable $e) {

            logger()->error('ProfileController.updatePassword error', [
                'user_id' => $request->user('sanctum')?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du mot de passe.'
            ], 500);
        }
    }

    public function likedMe(Request $request)
    {
        try {
            $user = $request->user('sanctum');

            $likedIds = ProfileAction::query()
                ->where('target_id', $user->id)
                ->where('type', 'like')
                ->pluck('actor_id');

            $handledIds = ProfileAction::query()
                ->where('actor_id', $user->id)
                ->whereIn('type', ['like', 'pop'])
                ->pluck('target_id');

            $profiles = User::query()
                ->with(['photos', 'interests'])
                ->whereIn('id', $likedIds)
                ->whereNotIn('id', $handledIds)
                ->get();

            return response()->json(
                $profiles->map(
                    fn(User $profile) => $this->profilePayload($profile, $user)
                )
            );
        } catch (\Throwable $e) {
            logger()->error('ProfileController.likedMe error', [
                'user_id' => $request->user('sanctum')?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la récupération des likes.'], 500);
        }
    }

    public function show(Request $request, User $user)
    {
        try {
            return response()->json($this->profilePayload($user->load(['photos', 'interests']), $request->user('sanctum')));
        } catch (\Throwable $e) {
            logger()->error('ProfileController.show error', [
                'profile_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la récupération du profil.'], 500);
        }
    }

    public function uploadPhoto(Request $request)
    {
        try {
            $data = $request->validate([
                'photo' => ['required', 'image', 'max:5120'],
            ]);

            $path = $data['photo']->store('profile-photos', 'public');
            $photo = ProfilePhoto::query()->create([
                'user_id' => $request->user('sanctum')->id,
                'path' => 'storage/' . $path,
                'url' => asset($path),
                'position' => ProfilePhoto::query()->where('user_id', $request->user('sanctum')->id)->count(),
                'is_primary' => ! ProfilePhoto::query()->where('user_id', $request->user('sanctum')->id)->exists(),
            ]);

            return response()->json(['url' => $photo->path], 201);
        } catch (\Throwable $e) {
            logger()->error('ProfileController.uploadPhoto error', [
                'user_id' => $request->user('sanctum')?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de l\'upload de la photo.'], 500);
        }
    }

    public function deletePhoto(Request $request, ProfilePhoto $photo)
    {
        try {
            $user = $request->user('sanctum');

            if ($photo->user_id !== $user->id) {
                return response()->json(['message' => 'Photo introuvable.'], 404);
            }

            $storagePath = str_replace('storage/', '', $photo->path);

            if (Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }

            $photo->delete();

            $remainingPhotos = $user->photos()->orderBy('position')->get();

            if ($remainingPhotos->count() && ! $remainingPhotos->where('is_primary', true)->count()) {
                $remainingPhotos->first()->update(['is_primary' => true]);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            logger()->error('ProfileController.deletePhoto error', [
                'user_id' => $request->user('sanctum')?->id,
                'photo_id' => $photo->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de la suppression de la photo.'], 500);
        }
    }

    private function profilePayload(User $profile, ?User $viewer = null): array
    {

        $liked = $viewer
            ? ProfileAction::where('actor_id', $viewer->id)
            ->where('target_id', $profile->id)
            ->where('type', 'like')
            ->exists()
            : false;

        $likedMe = $viewer
            ? ProfileAction::where('actor_id', $profile->id)
            ->where('target_id', $viewer->id)
            ->where('type', 'like')
            ->exists()
            : false;

        $likedMeAt = $likedMe
            ? ProfileAction::where('actor_id', $profile->id)
            ->where('target_id', $viewer->id)
            ->where('type', 'like')
            ->first()
            : null;

        $matched = $viewer
            ? MatchModel::query()
            ->where(function ($query) use ($viewer, $profile) {
                $query->where('user_one_id', $viewer->id)
                    ->where('user_two_id', $profile->id);
            })
            ->orWhere(function ($query) use ($viewer, $profile) {
                $query->where('user_one_id', $profile->id)
                    ->where('user_two_id', $viewer->id);
            })
            ->exists()
            : false;

        $poped = $viewer
            ? ProfileAction::where('actor_id', $viewer->id)
            ->where('target_id', $profile->id)
            ->where('type', 'pop')
            ->exists()
            : false;

        $popedMe = $viewer
            ? ProfileAction::where('actor_id', $profile->id)
            ->where('target_id', $viewer->id)
            ->where('type', 'pop')
            ->exists()
            : false;

        return [
            'id' => (string) $profile->id,
            'name' => $profile->displayName(),
            'age' => $profile->age() ?? 18,
            'city' => $profile->city ?? '',
            'country' => $profile->country ?? '',
            'bio' => $profile->bio ?? '',
            'intention' => $profile->intention ?? '',
            'verified' => (bool) $profile->verified,
            'distance' => '0 km',
            'pictures' => $profile->photos->map(fn(ProfilePhoto $photo) => [
                'id' => (string) $photo->id,
                'name' => $photo->path,
                'isPrimary' => (bool) $photo->is_primary,
            ])->values(),
            'avatar' => optional($profile->photos->first())->path ?? null,
            'interests' => $profile->interests->pluck('name')->values(),
            'liked' => $liked,
            'likedMe' => $likedMe,
            'matched' => $matched,
            'poped' => $poped,
            'popedMe' => $popedMe,
            'lastSeen' => optional($profile->last_seen_at)->toISOString(),
            'conversationId' => $matched ? MatchModel::getConversationId($viewer->id, $profile->id) : null,
            'likedMeAt' => $likedMeAt ? $likedMeAt->created_at->toISOString() : null,
        ];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'username' => $user->username,
            'phone' => $user->phone,
            'email' => $user->email,
            'birthDate' => optional($user->birth_date)->toDateString(),
            'gender' => $user->gender,
            'city' => $user->city,
            'country' => $user->country,
            'intention' => $user->intention,
            'bio' => $user->bio,
            'avatar' => optional($user->photos->first())->path ?? null,
            'pictures' => $user->photos->map(fn($photo) => ['id' => (string) $photo->id, 'name' => $photo->path])->values(),
            'age' => $user->age(),
            'verified' => (bool) $user->verified,
            'messageCredits' => MessageCredit::query()->where('user_id', $user->id)->get()->sum('available_messages'),
            'interests' => $user->interests->pluck('name')->values(),
        ];
    }
}
