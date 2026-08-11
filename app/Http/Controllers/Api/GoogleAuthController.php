<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirige l'utilisateur vers Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google revient ici après authentification.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $googleId = $googleUser->getId();
            $email = $googleUser->getEmail();

            if (!$googleId || !$email) {
                return $this->redirectToMobile([
                    'success' => false,
                    'code' => 'google_account_invalid',
                ]);
            }

            $email = Str::lower($email);

            /*
             * Google nous indique normalement si l'adresse
             * email est vérifiée.
             */
            $googleData = $googleUser->user ?? [];

            $emailVerified = $googleData['email_verified'] ?? $googleData['verified_email'] ?? false;

            if (!$emailVerified) {
                return $this->redirectToMobile([
                    'success' => false,
                    'code' => 'google_email_not_verified',
                ]);
            }

            /*
             * 1. On cherche d'abord par google_id.
             */
            $user = User::query()->where('google_id', $googleId)->first();

            /*
             * 2. Si pas trouvé, on cherche par email.
             *
             * Cela permet à un utilisateur qui possède déjà
             * un compte PopTheBallon avec cet email de connecter
             * son compte Google.
             */
            if (!$user) {
                $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            }

            /*
             * Si le compte existe.
             */
            if ($user) {

                if ($user->deleted_at) {
                    return $this->redirectToMobile([
                        'success' => false,
                        'code' => 'account_deleted',
                    ]);
                }

                if ($user->is_staff) {
                    return $this->redirectToMobile([
                        'success' => false,
                        'code' => 'staff_login_not_allowed',
                    ]);
                }

                /*
                 * On associe le compte Google au compte existant.
                 */
                if (!$user->google_id) {
                    $user->google_id = $googleId;
                    $user->save();
                }

                $user->last_seen_at = now();
                $user->save();

                /*
                 * On ne met PAS le token Sanctum dans l'URL.
                 *
                 * On génère un code temporaire.
                 */
                $code = $this->createExchangeCode([
                    'type' => 'login',
                    'user_id' => $user->id,
                ]);

                return $this->redirectToMobile([
                    'success' => true,
                    'code' => $code,
                ]);
            }

            /*
             * Aucun compte PopTheBallon correspondant.
             *
             * Google ne fournit pas toutes les informations
             * obligatoires de ton inscription actuelle.
             *
             * On conserve donc temporairement les informations
             * Google pour permettre au mobile de terminer
             * l'inscription.
             */
            $code = $this->createExchangeCode([
                'type' => 'register',
                'google_id' => $googleId,
                'email' => $email,
                'first_name' => $googleUser->user['given_name'] ?? $googleUser->getName() ?? '',
                'last_name' => $googleUser->user['family_name'] ?? '',
                'avatar' => $googleUser->user['avatar'] ?? $googleUser->getAvatar() ?? '',
            ]);

            return $this->redirectToMobile([
                'success' => true,
                'code' => $code,
                'next' => 'complete_profile',
            ]);
        } catch (\Throwable $e) {

            logger()->error('Google OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->redirectToMobile([
                'success' => false,
                'code' => 'google_auth_failed',
            ]);
        }
    }

    /**
     * Transforme le code temporaire en authentification Sanctum.
     */
    public function exchange(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $cacheKey = 'google_oauth_exchange:' . hash('sha256', $data['code']);

        $payload = Cache::pull($cacheKey);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'code' => 'invalid_or_expired_code',
                'message' => 'Le code Google est invalide ou expiré.',
            ], 422);
        }

        /*
         * Connexion d'un utilisateur existant.
         */
        if (($payload['type'] ?? null) === 'login') {

            $user = User::find($payload['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 'user_not_found',
                ], 404);
            }

            if ($user->deleted_at) {
                return response()->json([
                    'success' => false,
                    'code' => 'account_deleted',
                ], 403);
            }

            return response()->json(
                $user->authResponse()
            );
        }

        /*
         * Nouveau compte.
         *
         * Le mobile doit maintenant demander les informations
         * manquantes : username, téléphone, date de naissance,
         * etc.
         */
        if (($payload['type'] ?? null) === 'register') {

            return response()->json([
                'success' => true,
                'next' => 'complete_profile',
                'google' => [
                    'email' => $payload['email'],
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'avatar' => $payload['avatar'],
                ],
                'registration_token' => $this->createExchangeCode([
                    'type' => 'google_registration',
                    'google_id' => $payload['google_id'],
                    'email' => $payload['email'],
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'avatar' => $payload['avatar'],
                ]),
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => 'invalid_google_flow',
        ], 422);
    }

    /**
     * Génère un code temporaire à usage unique.
     */
    private function createExchangeCode(array $payload): string
    {
        $code = Str::random(64);

        Cache::put(
            'google_oauth_exchange:' . hash('sha256', $code),
            $payload,
            now()->addMinutes(5)
        );

        return $code;
    }

    /**
     * Redirige vers l'application Expo.
     */
    private function redirectToMobile(array $params)
    {
        $query = http_build_query($params);

        //dd('poptheballon://auth/google?' . $query);

        return redirect(
            'poptheballon://auth/google?' . $query
        );
    }

}
