<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppleAuthController extends Controller
{
    /**
     * Authentification avec Sign in with Apple.
     *
     * Le mobile effectue directement l'authentification
     * auprès d'Apple puis envoie ici :
     *
     * - identity_token
     * - authorization_code
     * - apple_user_id
     * - email (disponible principalement lors de la première connexion)
     * - first_name
     * - last_name
     */
    public function authenticate(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation de la requête
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'identity_token' => ['required','string'],
            'authorization_code' => ['required','string',],
            'apple_user_id' => ['required','string','max:255'],
            /*
             * Apple peut retourner null après la première connexion.
             */
            'email' => ['nullable','email','max:255'],
            'first_name' => ['nullable','string','max:100'],
            'last_name' => ['nullable','string','max:100']
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | 2. Valider l'authorization code auprès d'Apple
            |--------------------------------------------------------------------------
            |
            | Apple recommande de transmettre le code d'autorisation
            | au serveur afin de le valider auprès de :
            |
            | https://appleid.apple.com/auth/token
            |
            */

            $appleTokenResponse = $this->validateAuthorizationCode(
                $data['authorization_code']
            );

            if (!$appleTokenResponse) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_authorization_invalid',
                    'message' => 'L’autorisation Apple est invalide ou expirée.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Récupération de l'identity token retourné par Apple
            |--------------------------------------------------------------------------
            |
            | Apple retourne un nouvel id_token lors de la validation
            | de l'authorization code.
            |
            */

            $appleIdentityToken = $appleTokenResponse['id_token'] ?? null;

            if (!$appleIdentityToken) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_identity_token_missing',
                    'message' => 'Apple n’a pas retourné de token d’identité.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Décodage et validation des claims du token
            |--------------------------------------------------------------------------
            */

            $claims = $this->decodeIdentityToken($appleIdentityToken);

            if (!$claims) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_identity_token_invalid',
                    'message' => 'Le token d’identité Apple est invalide.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Vérification de l'identité Apple
            |--------------------------------------------------------------------------
            */

            $tokenAppleUserId = $claims['sub'] ?? null;

            if (!$tokenAppleUserId) {
                return response()->json([
                    'success' => false,
                    'code' => 'apple_user_id_missing',
                    'message' => 'L’identifiant Apple est manquant.',
                ], 422);
            }

            /*
             * L'identifiant fourni par le mobile doit correspondre
             * au "sub" du token signé par Apple.
             */
            if ($tokenAppleUserId !== $data['apple_user_id']) {
                Log::warning('Apple user ID mismatch', [
                    'request_user_id' => $data['apple_user_id'],
                    'token_user_id' => $tokenAppleUserId,
                ]);

                return response()->json([
                    'success' => false,
                    'code' => 'apple_user_mismatch',
                    'message' => 'L’identité Apple ne correspond pas aux informations reçues.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Vérification de l'audience
            |--------------------------------------------------------------------------
            */

            $expectedClientId = config('services.apple.client_id');

            if (
                !$expectedClientId ||
                ($claims['aud'] ?? null) !== $expectedClientId
            ) {
                Log::warning('Apple audience mismatch', [
                    'expected' => $expectedClientId,
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
            | 7. Vérification de l'émetteur
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
            | 8. Vérification de l'expiration
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
            | 9. Email
            |--------------------------------------------------------------------------
            |
            | L'email peut être absent du credential mobile lors
            | des connexions suivantes.
            |
            | Apple fournit cependant normalement l'email dans
            | l'identity token.
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
            | 10. Recherche du compte par Apple ID
            |--------------------------------------------------------------------------
            |
            | IMPORTANT :
            |
            | apple_user_id est notre identifiant principal.
            |
            */

            $user = User::query()
                ->where('apple_id', $tokenAppleUserId)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | 11. Si aucun compte trouvé par Apple ID
            |--------------------------------------------------------------------------
            |
            | On peut également tenter de retrouver un compte existant
            | par email.
            |
            | Cela permet de connecter Apple à un compte Pop The Ballon
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
            | 12. Compte existant
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
                | Compte staff
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
                | Association Apple
                |--------------------------------------------------------------------------
                |
                | Si l'utilisateur possédait déjà un compte avec cet email,
                | on associe maintenant son Apple ID.
                |
                */

                if (!$user->apple_id) {
                    $user->apple_id = $tokenAppleUserId;
                }

                /*
                |--------------------------------------------------------------------------
                | Mise à jour des informations disponibles
                |--------------------------------------------------------------------------
                |
                | Apple ne renvoie pas toujours le nom après la première
                | connexion. On ne remplace donc jamais une valeur existante
                | par null.
                |
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
                | Connexion finale
                |--------------------------------------------------------------------------
                */

                return response()->json(
                    $user->authResponse()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 13. Aucun compte correspondant
            |--------------------------------------------------------------------------
            |
            | Il faut maintenant terminer l'inscription dans le mobile.
            |
            */

            $registrationToken = $this->createRegistrationToken([
                'type' => 'apple_registration',
                'apple_id' => $tokenAppleUserId,
                'email' => $email,
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                /*
                 * On conserve le refresh token afin de pouvoir gérer
                 * correctement le cycle de vie de Sign in with Apple.
                 */
                'refresh_token' => $appleTokenResponse['refresh_token'] ?? null,
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
     * Valide l'authorization code auprès d'Apple.
     *
     * Apple utilise :
     *
     * POST https://appleid.apple.com/auth/token
     */
    private function validateAuthorizationCode(string $authorizationCode): ?array {

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://appleid.apple.com/auth/token',
                [
                    'client_id' => config('services.apple.client_id'),
                    'client_secret' => $this->generateClientSecret(),
                    'code' => $authorizationCode,
                    'grant_type' => 'authorization_code',
                ]
            );

        if (!$response->successful()) {
            Log::warning('Apple token validation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
        }

        return $response->json();
    }


    /**
     * Décode l'identity token Apple.
     *
     * IMPORTANT :
     * Cette méthode sera remplacée par une vraie vérification
     * cryptographique de la signature Apple.
     */
    private function decodeIdentityToken(string $identityToken): ?array {

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

        if (!$decoded) {
            return null;
        }

        $data = json_decode($decoded,true);
        return is_array($data) ? $data: null;
    }


    /**
     * Génère le client secret Apple.
     */
    private function generateClientSecret(): string
    {
        /*
         * À implémenter avec :
         *
         * - Apple Team ID
         * - Apple Key ID
         * - Apple private key
         * - Apple client ID
         *
         * Nous allons le faire dans l'étape suivante.
         */

        throw new \RuntimeException(
            'Apple client secret generation is not configured yet.'
        );
    }


    /**
     * Génère un token temporaire pour terminer l'inscription.
     */
    private function createRegistrationToken(array $payload, int $minutes = 30): string {

        $token = Str::random(64);

        cache()->put(
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
