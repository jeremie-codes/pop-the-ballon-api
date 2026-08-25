<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{

    /**
     * Redirige l'utilisateur vers Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->with([
            'prompt' => 'select_account',
        ])
            ->redirect();
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
                ], 30),
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
    private function createExchangeCode(
        array $payload,
        int $minutes = 5
    ): string {
        $code = Str::random(64);

        Cache::put(
            'google_oauth_exchange:' . hash('sha256', $code),
            $payload,
            now()->addMinutes($minutes)
        );

        return $code;
    }

    private function redirectToMobile(array $params)
    {
        return response()->view('auth.google-mobile-result', [
            'success' => $params['success'] ?? false,
            'code' => $params['code'] ?? null,
            'next' => $params['next'] ?? null,
        ]);
    }


    /**
     * Termine l'inscription commencée avec Google.
     */
    public function completeRegistration(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation des données
        |--------------------------------------------------------------------------
        |
        | On ne met volontairement PAS la validation dans le try/catch.
        | Ainsi Laravel pourra retourner correctement une erreur 422
        | au lieu de la transformer en erreur 500.
        |
        */

        $data = $request->validate(
            [
                'registration_token' => ['required', 'string'],
                'username' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/',],
                'phone' => ['nullable', 'string', 'max:30'],
                'birth_date' => ['required', 'date'],
                'gender' => ['required', 'string', 'max:50',],
                'city' => ['required', 'string', 'max:120',],
                'country' => ['required', 'string', 'max:120'],
                'intention' => ['required', 'string', 'max:255'],
                'bio' => ['nullable', 'string'],
                'interests' => ['nullable', 'array'],
                'interests.*' => ['string', 'max:80'],
            ],
            [
                'registration_token.required' => 'La session d’inscription Google est invalide.',
                'username.required' => 'Veuillez choisir un nom d’utilisateur.',
                'username.max' => 'Votre nom d’utilisateur ne peut pas dépasser 30 caractères.',
                'username.regex' => 'Le nom d’utilisateur doit contenir uniquement des lettres, des chiffres et le caractère _.',
                'birth_date.required' => 'Votre date de naissance est obligatoire.',
                'birth_date.date' => 'La date de naissance renseignée est invalide.',
                'gender.required' => 'Veuillez sélectionner votre genre.',
                'city.required' => 'Veuillez renseigner votre ville.',
                'country.required' => 'Veuillez sélectionner votre pays.',
                'intention.required' => 'Veuillez indiquer votre intention.',
                'interests.array' => 'Le format des centres d’intérêt est invalide.',
                'interests.*.string' => 'Un centre d’intérêt sélectionné est invalide.',
                'interests.*.max' => 'Un centre d’intérêt ne peut pas dépasser 80 caractères.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Normalisation du username et du téléphone
        |--------------------------------------------------------------------------
        */

        $username = Str::lower(trim($data['username']));

        /*
        * On supprime les espaces et caractères non numériques
        * du numéro de téléphone.
        *
        * Exemple :
        * +243 997 365 080
        * devient :
        * 243997365080
        */
        $phone = $data['phone'] ? preg_replace('/\D+/', '', $data['phone']): null;

        /*
        |--------------------------------------------------------------------------
        | 3. Vérification personnalisée du username
        |--------------------------------------------------------------------------
        */

        if (User::query()->whereRaw('LOWER(username) = ?', [$username])->exists()) {
            return response()->json([
                'success' => false,
                'code' => 'username_already_taken',
                'field' => 'username',
                'message' =>
                'Ce nom d’utilisateur est déjà utilisé. Veuillez en choisir un autre.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Vérification personnalisée du téléphone
        |--------------------------------------------------------------------------
        */

        if (User::query()->where('phone', $phone)->exists()) {
            return response()->json([
                'success' => false,
                'code' => 'phone_already_taken',
                'field' => 'phone',
                'message' =>
                'Ce numéro de téléphone est déjà associé à un compte.',
            ], 422);
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | 5. Récupération du token Google
            |--------------------------------------------------------------------------
            */

            $cacheKey = 'google_oauth_exchange:' . hash(
                'sha256',
                $data['registration_token']
            );

            /*
            * IMPORTANT :
            *
            * pull() signifie que le token est consommé.
            *
            * Une fois récupéré, il ne pourra plus être réutilisé.
            */
            $payload = Cache::pull($cacheKey);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'code' => 'invalid_or_expired_registration',
                    'message' =>
                    'La session d’inscription Google est invalide ou expirée.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Vérification du type du token
            |--------------------------------------------------------------------------
            */

            if (($payload['type'] ?? null) !== 'google_registration') {
                return response()->json([
                    'success' => false,
                    'code' => 'invalid_registration_token',
                    'message' =>
                    'Le token d’inscription Google est invalide.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 7. Vérification du compte Google
            |--------------------------------------------------------------------------
            |
            */

            $existingUser = User::query()
                ->where('google_id', $payload['google_id'])
                ->orWhereRaw(
                    'LOWER(email) = ?',
                    [Str::lower($payload['email'])]
                )
                ->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'code' => 'account_already_exists',
                    'message' =>
                    'Un compte existe déjà avec ce compte Google.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Création du compte
            |--------------------------------------------------------------------------
            */

            $user = DB::transaction(function () use ($data, $payload, $username, $phone) {

                $user = User::query()->create([

                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'email' => $payload['email'],
                    'google_id' => $payload['google_id'],

                    'username' => $username,
                    'phone' => $phone,
                    'password' => null,

                    'birth_date' => $data['birth_date'],
                    'gender' => $data['gender'],
                    'city' => $data['city'],
                    'country' => $data['country'],
                    'intention' => $data['intention'],
                    'bio' => $data['bio'] ?? null,

                    'last_seen_at' => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Centres d'intérêt
                |--------------------------------------------------------------------------
                */

                foreach (
                    $data['interests'] ?? [] as $interest
                ) {
                    $user->interests()->create([
                        'name' => $interest,
                    ]);
                }

                return $user->load([
                    'interests',
                    'photos',
                ]);
            });

            /*
            |--------------------------------------------------------------------------
            | 9. Réponse finale
            |--------------------------------------------------------------------------
            */

            return response()->json(
                $user->authResponse(),
                201
            );
        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | 10. Erreur serveur
            |--------------------------------------------------------------------------
            */

            logger()->error(
                'Google complete registration error',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'code' => 'google_registration_failed',
                'message' =>
                'Impossible de terminer votre inscription. Veuillez réessayer.',
            ], 500);
        }
    }
}
