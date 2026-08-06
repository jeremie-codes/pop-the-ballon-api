<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageCredit;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $data = $request->validate([
                'identifier' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);

            $identifier = Str::lower($data['identifier']);
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$identifier])
                ->where('is_visible', true)
                ->where('role', '!=', 'admin')
                ->orWhereRaw('LOWER(username) = ?', [$identifier])
                ->orWhere('phone', $data['identifier'])
                ->first();

            if ($user && $user->deleted_at) {
                return response()->json([
                    'success' => false,
                    'code' => 'account_deleted',
                    'message' => 'Ce compte a été supprimé.'
                ], 403);
            }

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return response()->json(['message' => 'Identifiant ou mot de passe incorrect'], 422);
            }

            $user->last_seen_at = now();
            $user->save();

            return response()->json($this->authResponse($user));
        } catch (\Throwable $e) {
            logger()->error('AuthController.login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur interne', 'error' => $e->getMessage()], 500);
        }
    }

    public function savePushToken(Request $request)
    {
        try {
            $request->validate([
                'token' => ['required', 'string'],
                'platform' => ['required', 'string'],
            ]);

            $user = $request->user('sanctum');

            if (! $user) {
                return response()->json(['message' => 'Utilisateur non authentifie'], 401);
            }

            UserDevice::where('user_id', $user->id)
                ->where('platform', $request->platform)
                ->where('expo_token', '!=', $request->token)
                ->delete();

            UserDevice::updateOrCreate(
                ['expo_token' => $request->token],
                [
                    'user_id' => $user->id,
                    'platform' => $request->platform,
                    'last_used_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Token de notification mis a jour'
            ]);
        } catch (\Throwable $e) {
            logger()->error('AuthController.savePushToken error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur interne', 'error' => $e->getMessage()], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'username' => 'required|string|max:255|unique:users,username',
                'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
                'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::min(8)],
                'birth_date'=>[ 'required', 'date'],
                'gender' => ['nullable', 'string', 'max:50'],
                'city' => ['nullable', 'string', 'max:120'],
                'country' => ['nullable', 'string', 'max:120'],
                'intention' => ['nullable', 'string', 'max:255'],
                'bio' => ['nullable', 'string'],
                'interests' => ['nullable', 'array'],
                'interests.*' => ['string', 'max:80'],
            ]);

            $user = DB::transaction(function () use ($data) {
                $user = User::query()->create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'username' => Str::lower($data['username']),
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'password' => $data['password'],
                    'birth_date' => $data['birth_date'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? null,
                    'intention' => $data['intention'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'last_seen_at' => now(),
                ]);

                foreach ($data['interests'] ?? [] as $interest) {
                    $user->interests()->create(['name' => $interest]);
                }

                return $user->load(['interests', 'photos']);
            });

            return response()->json($this->authResponse($user), 201);
        } catch (\Throwable $e) {
            logger()->error('AuthController.register error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur, ' . $e->getMessage(), 'error' => $e->getMessage()], 500);
        }
    }

    public function checkIdentity(Request $request)
    {
        $data = $request->validate([
            'username'=>'required|string',
            'phone'=>'required|string',
            'email'=>'nullable|email',
        ]);

        $email = Str::lower($data['email']);

        $reserved = [
            'admin',
            'support',
            'help',
            'root',
            'system',
            'api',
            'contact',
            'staff',
            'official',
            'poptheballon',
        ];

        if (in_array(Str::lower($data['username']), $reserved)) {
            return response()->json([
                'username_available' => false,
                'phone_available' => !User::where('phone',$data['phone'])->exists(),
                'email_available' => empty($email) || !User::where('email', $email)->exists(),
            ]);
        }

        return response()->json([
            'username_available' => !User::where('username',Str::lower($data['username']))->exists(),
            'phone_available' => !User::where('phone',$data['phone'])->exists(),
            'email_available' => empty($email) || !User::where('email', $email)->exists(),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        try {

            $data = $request->validate([
                'identifier' => ['required', 'string'],
            ]);

            $identifier = Str::lower(trim($data['identifier']));

            $user = User::query()
                ->where(function ($query) use ($identifier, $data) {

                    $query
                        ->whereRaw('LOWER(email) = ?', [$identifier])
                        ->orWhereRaw('LOWER(username) = ?', [$identifier])
                        ->orWhere('phone', $data['identifier']);
                })
                ->first();


            if (! $user) {

                return response()->json([
                    'success' => true,
                    'message' => 'Si un compte existe, un code sera envoyé.'
                ]);
            }

            if (blank($user->email)) {

                return response()->json([
                    'success' => false,
                    'code' => 'email_required',
                    'message' => "Votre compte n'a pas d'adresse email."
                ], 422);
            }

            // Vérification blocage temporaire
            if (
                $user->password_reset_blocked_until &&
                now()->lessThan($user->password_reset_blocked_until)
            ) {
                $minutes = now()->diffInMinutes(
                    $user->password_reset_blocked_until
                );

                return response()->json([
                    'success' => false,
                    'message' => "Réessayez dans {$minutes} minute(s)."
                ], 422);
            }

            // Anti spam renvoi OTP
            if (
                $user->password_reset_last_sent_at
                &&
                now()->diffInSeconds(
                    $user->password_reset_last_sent_at
                ) < 60
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez attendre avant de demander un nouveau code.'
                ], 422);
            }

            if (
                $user->password_reset_last_sent_at
                &&
                $user->password_reset_last_sent_at->isToday()
                &&
                $user->password_reset_requests >= 5
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Nombre maximum de demandes atteint aujourd’hui.'
                ], 422);
            }

            // génération OTP
            $otp = random_int(100000, 999999);

            $user->update([
                'password_reset_otp' => $otp,
                'password_reset_otp_expires_at' => now()->addMinutes(10),
                'password_reset_attempts' => 0,
                'password_reset_requests' => $user->password_reset_last_sent_at && $user->password_reset_last_sent_at->isToday() ? $user->password_reset_requests + 1 : 1,
                'password_reset_last_sent_at' => now(),
            ]);

            // envoi mail
            Mail::raw(
                "Votre code de récupération est : {$otp}",
                function ($message) use ($user) {
                    $message->to($user->email)->subject("Code de récupération du mot de passe");
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Un code a été envoyé à votre adresse email.'
            ]);
        } catch (\Throwable $e) {
            logger()->error(
                'forgotPassword error',
                [
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erreur interne'
            ], 500);
        }
    }

    public function verifyResetOtp(Request $request)
    {
        try {

            $data = $request->validate([
                'identifier' => ['required', 'string'],
                'otp' => ['required', 'string']
            ]);

            $identifier = Str::lower(trim($data['identifier']));

            $user = User::query()
                ->where(function ($query) use ($identifier, $data) {
                    $query->whereRaw('LOWER(email)=?', [$identifier])
                        ->orWhereRaw('LOWER(username)=?', [$identifier])
                        ->orWhere('phone', $data['identifier']);
                })
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte introuvable'
                ], 404);
            }

            if ($user->password_reset_attempts >= 5) {
                $user->update([
                    'password_reset_blocked_until' => now()->addMinutes(1)
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Trop de tentatives. Compte temporairement bloqué. Ressayez dans 1 minute.'
                ], 422);
            }

            if ($user->password_reset_otp !== $data['otp'] || now()->greaterThan($user->password_reset_otp_expires_at)) {

                $user->increment('password_reset_attempts');

                return response()->json([
                    'success' => false,
                    'message' => 'Code invalide ou expiré'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'reset_token' => encrypt([
                    'user_id' => $user->id,
                    'time' => now()
                ])

            ]);
        } catch (\Throwable $e) {
            logger()->error(
                'verifyResetOtp error',
                [
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erreur interne'
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $data = $request->validate([
                'reset_token' => 'required|string',
                'password' => ['required', 'confirmed', Password::min(8)]
            ]);

            $payload = decrypt($data['reset_token']);

            $user = User::findOrFail(
                $payload['user_id']
            );

            $user->update([
                'password' => Hash::make($data['password']),
                'password_reset_otp' => null,
                'password_reset_otp_expires_at' => null,
                'remember_token' => Str::random(60)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès.'
            ]);
        } catch (\Throwable $e) {
            logger()->error(
                'resetPassword error',
                [
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Lien de récupération invalide'
            ], 422);
        }
    }

    private function authResponse(User $user): array
    {
        $token = $user->createToken('mobile', ['*'], now()->addDays(30));

        return [
            'code' => 'auth-ok',
            'token' => $token->plainTextToken,
            'expoToken' => $user->devices()->latest('last_used_at')->value('expo_token') ?? null,
            'expire_in' => 60 * 60 * 24 * 30,
            'merchant' => '',
            'shop' => '',
            'is_merchant' => false,
            'is_super_merchant' => false,
            'user' => [
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
                'avatar' => optional($user->photos->first())->path,
                'pictures' => $user->photos->map(fn($photo) => [
                    'id' => (string) $photo->id,
                    'name' => $photo->path,
                    'isPrimary' => (bool) $photo->is_primary,
                ])->values(),
                'age' => $user->age(),
                'verified' => (bool) $user->verified,
                'messageCredits' => MessageCredit::query()->where('user_id', $user->id)->get()->sum('available_messages'),
                'interests' => $user->interests->pluck('name')->values(),
            ],
        ];
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 422);
        }

        DB::transaction(function () use ($user, $request) {

            $user->update([
                'deleted_at' => now(),
                'delete_reason' => $request->reason,
                'is_visible' => false,
                'remember_token' => Str::random(60),
            ]);

            UserDevice::where('user_id', $user->id)->delete();

            $user->tokens()->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Votre compte a été supprimé.'
        ]);
    }
}
