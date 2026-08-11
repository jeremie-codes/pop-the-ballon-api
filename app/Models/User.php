<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Carbon;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'google_id',
        'expo_token',
        'first_name',
        'last_name',
        'username',
        'role',
        'phone',
        'email',
        'password',
        'birth_date',
        'is_staff',
        'gender',
        'city',
        'country',
        'intention',
        'bio',
        'verified',
        'avatar',
        'is_visible',
        'last_seen_at',
        'deleted_at',
        'delete_reason',
        'password_reset_attempts',
        'password_reset_requests',
        'password_reset_otp_expires_at',
        'password_reset_last_sent_at',
        'password_reset_blocked_until',
    ];

    public function interests()
    {
        return $this->hasMany(UserInterest::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function photos()
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('position');
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function messageCredit()
    {
        return $this->hasOne(MessageCredit::class);
    }

    public function sentActions()
    {
        return $this->hasMany(ProfileAction::class, 'actor_id');
    }

    public function receivedActions()
    {
        return $this->hasMany(ProfileAction::class, 'target_id');
    }

    public function displayName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->username ?: 'Utilisateur';
    }

    public function age(): ?int
    {
        return $this->birth_date instanceof Carbon ? $this->birth_date->age : null;
    }

    public function getFilamentName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'verified' => 'boolean',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
            'password_reset_otp_expires_at' => 'datetime',
            'password_reset_last_sent_at'=>'datetime',
            'password_reset_blocked_until'=>'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isSupport(): bool
    {
        return $this->is_staff;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function supportConversations()
    {
        return Conversation::where('type', 'support')
            ->where(function ($query) {

                $query->where('user_one_id', $this->id)
                    ->orWhere('user_two_id', $this->id);
            });
    }

    public function authResponse(): array
    {
        $token = $this->createToken('mobile', ['*'], now()->addDays(30));

        return [
            'code' => 'auth-ok',
            'token' => $token->plainTextToken,
            'expoToken' => $this->devices()->latest('last_used_at')->value('expo_token') ?? null,
            'expire_in' => 60 * 60 * 24 * 30,
            'merchant' => '',
            'shop' => '',
            'is_merchant' => false,
            'is_super_merchant' => false,
            'user' => [
                'id' => (string) $this->id,
                'firstName' => $this->first_name,
                'lastName' => $this->last_name,
                'username' => $this->username,
                'phone' => $this->phone,
                'email' => $this->email,
                'birthDate' => optional($this->birth_date)->toDateString(),
                'gender' => $this->gender,
                'city' => $this->city,
                'country' => $this->country,
                'intention' => $this->intention,
                'bio' => $this->bio,
                'avatar' => optional($this->photos->first())->path,
                'pictures' => $this->photos->map(fn($photo) => [
                    'id' => (string) $photo->id,
                    'name' => $photo->path,
                    'isPrimary' => (bool) $photo->is_primary,
                ])->values(),
                'age' => $this->age(),
                'verified' => (bool) $this->verified,
                'messageCredits' => MessageCredit::query()->where('user_id', $this->id)->get()->sum('available_messages'),
                'interests' => $this->interests->pluck('name')->values(),
            ],
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_staff;
    }
}
