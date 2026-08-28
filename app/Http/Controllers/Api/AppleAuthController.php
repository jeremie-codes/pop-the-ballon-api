<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppleAuthController extends Controller
{
    /**
     * Authentification avec Sign in with Apple.
     *
     * Le mobile effectue directement l'authentification
     * auprès d'Apple puis envoie :
     *
     * - identity_token
     * - apple_user_id
     * - email
     * - first_name
     * - last_name
     */
    public function authenticate(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'identity_token' => ['required', 'string'],
            'apple_user_id' => ['required', 'string', 'max:255'],

            /*
             * Apple peut ne plus envoyer ces informations
             * lors des connexions suivantes.
             */
            'email' => ['nullable', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | 2. Décoder l'identity token
            |--------------------------------------------------------------------------
            */

            $claims = $this->decodeIdentityToken(
                $data['identity_token']
            );

            if (!$claims) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_identity_token_invalid',
                    'message' => 'Le token Apple est invalide.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Vérifier l'identifiant Apple
            |--------------------------------------------------------------------------
            */

            $appleUserId = $claims['sub'] ?? null;

            if (!$appleUserId) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_user_id_missing',
                    'message' => 'L’identifiant Apple est manquant.',
                ], 422);
            }

            /*
             * L'identifiant envoyé par le mobile doit
             * correspondre à celui présent dans le JWT.
             */
            if ($appleUserId !== $data['apple_user_id']) {

                Log::warning('Apple user ID mismatch', [
                    'request_user_id' => $data['apple_user_id'],
                    'token_user_id' => $appleUserId,
                ]);

                return response()->json([
                    'success' => false,
                    'code' => 'apple_user_mismatch',
                    'message' => 'L’identité Apple ne correspond pas.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Vérifier l'audience
            |--------------------------------------------------------------------------
            */

            $clientId = config('services.apple.client_id');

            if (!$clientId) {
                throw new \RuntimeException(
                    'Apple client_id is not configured.'
                );
            }

            if (($claims['aud'] ?? null) !== $clientId) {

                Log::warning('Apple audience mismatch', [
                    'expected' => $clientId,
                    'received' => $claims['aud'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'code' => 'apple_client_invalid',
                    'message' => 'Le token Apple n’est pas destiné à cette application.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Vérifier l'émetteur
            |--------------------------------------------------------------------------
            */

            if (
                ($claims['iss'] ?? null)
                !== 'https://appleid.apple.com'
            ) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_issuer_invalid',
                    'message' => 'Le token Apple est invalide.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Vérifier l'expiration
            |--------------------------------------------------------------------------
            */

            if (
                !isset($claims['exp']) ||
                now()->timestamp >= (int) $claims['exp']
            ) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_token_expired',
                    'message' => 'Le token Apple a expiré.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 7. Email
            |--------------------------------------------------------------------------
            |
            | Apple peut mettre l'email dans le JWT.
            |
            | On utilise d'abord celui du JWT puis celui envoyé
            | par le mobile.
            |
            */

            $email = $claims['email']
                ?? $data['email']
                ?? null;

            if ($email) {
                $email = Str::lower(trim($email));
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Recherche du compte par Apple ID
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->where('apple_id', $appleUserId)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | 9. Recherche éventuelle par email
            |--------------------------------------------------------------------------
            |
            | Permet de connecter Apple à un compte Pop The Ballon
            | qui existe déjà avec la même adresse email.
            |
            */

            if (!$user && $email) {
                $user = User::query()
                    ->whereRaw(
                        'LOWER(email) = ?',
                        [$email]
                    )
                    ->first();
            }

            /*
            |--------------------------------------------------------------------------
            | 10. COMPTE EXISTANT
            |--------------------------------------------------------------------------
            */

            if ($user) {

                /*
                |--------------------------------------------------------------------------
                | Compte supprimé
                |--------------------------------------------------------------------------
                */

                if ($user->deleted_at) {
                    return response()->json([
                        'success' => false,
                        'code' => 'account_deleted',
                    ], 403);
                }

                /*
                |--------------------------------------------------------------------------
                | Staff
                |--------------------------------------------------------------------------
                */

                if ($user->is_staff) {
                    return response()->json([
                        'success' => false,
                        'code' => 'staff_login_not_allowed',
                    ], 403);
                }

                /*
                |--------------------------------------------------------------------------
                | Associer Apple au compte
                |--------------------------------------------------------------------------
                */

                if (!$user->apple_id) {
                    $user->apple_id = $appleUserId;
                }

                /*
                |--------------------------------------------------------------------------
                | Compléter les informations si elles sont absentes
                |--------------------------------------------------------------------------
                */

                if (!$user->first_name && !empty($data['first_name'])) {
                    $user->first_name = $data['first_name'];
                }

                if (!$user->last_name && !empty($data['last_name'])) {
                    $user->last_name = $data['last_name'];
                }

                if (!$user->email && $email) {
                    $user->email = $email;
                }

                $user->last_seen_at = now();

                $user->save();

                /*
                |--------------------------------------------------------------------------
                | Connexion
                |--------------------------------------------------------------------------
                */

                return response()->json(
                    $user->authResponse()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 11. NOUVEL UTILISATEUR
            |--------------------------------------------------------------------------
            */

            $registrationToken = $this->createRegistrationToken([
                'type' => 'apple_registration',

                'apple_id' => $appleUserId,

                'email' => $email,

                'first_name' => $data['first_name'] ?? '',

                'last_name' => $data['last_name'] ?? '',
            ]);

            return response()->json([
                'success' => true,
                'next' => 'complete_profile',
                'apple' => [
                    'email' => $email,
                    'first_name' => $data['first_name'] ?? '',
                    'last_name' => $data['last_name'] ?? '',
                ],

                'registration_token' => $registrationToken,
            ]);
        } catch (\Throwable $e) {

            Log::error('Apple authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'apple_auth_failed',
                'message' => 'Impossible de vous connecter avec Apple.',
            ], 500);
        }
    }


    /**
     * Décode le payload du JWT Apple.
     *
     * IMPORTANT :
     * Cette méthode décode le JWT mais ne vérifie pas encore
     * cryptographiquement sa signature.
     */
    private function decodeIdentityToken(string $identityToken): ?array
    {
        $parts = explode('.', $identityToken);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = $parts[1];

        $payload .= str_repeat(
            '=',
            (4 - strlen($payload) % 4) % 4
        );

        $decoded = base64_decode(
            strtr(
                $payload,
                '-_',
                '+/'
            )
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
    }


    /**
     * Génère un token temporaire pour terminer
     * l'inscription Apple.
     */
    private function createRegistrationToken(
        array $payload,
        int $minutes = 30
    ): string {

        $token = Str::random(64);

        Cache::put(
            'apple_registration:' . hash(
                'sha256',
                $token
            ),
            $payload,
            now()->addMinutes($minutes)
        );

        return $token;
    }
}
