<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Identité
                |--------------------------------------------------------------------------
                */

                Section::make('Identité')
                    ->description('Informations personnelles principales de l’utilisateur.')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([

                        TextInput::make('first_name')
                            ->label('Prénom')
                            ->placeholder('Prénom')
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label('Nom')
                            ->placeholder('Nom')
                            ->maxLength(255),

                        TextInput::make('username')
                            ->label("Nom d'utilisateur")
                            ->prefix('@')
                            ->maxLength(255),

                        DatePicker::make('birth_date')
                            ->label('Date de naissance')
                            ->native(false),

                        Select::make('gender')
                            ->label('Genre')
                            ->options([
                                'Homme' => 'Homme',
                                'Femme' => 'Femme',
                            ])
                            ->native(false),

                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->placeholder('+243...')
                            ->maxLength(30),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Compte
                |--------------------------------------------------------------------------
                */

                Section::make('Compte & authentification')
                    ->description('Informations utilisées pour la connexion au compte.')
                    ->icon('heroicon-o-lock-closed')
                    ->columns(2)
                    ->schema([

                        TextInput::make('email')
                            ->label('Adresse email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->dehydrated(
                                fn(?string $state): bool => filled($state)
                            )
                            ->nullable()
                            ->helperText(
                                'Laissez vide pour conserver le mot de passe actuel.'
                            ),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email vérifié le')
                            ->native(false),

                        TextInput::make('google_id')
                            ->label('Google ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(
                                'Identifiant Google associé au compte.'
                            ),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Profil
                |--------------------------------------------------------------------------
                */

                Section::make('Profil')
                    ->description('Informations visibles sur le profil de l’utilisateur.')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([

                        Textarea::make('bio')
                            ->label('Biographie')
                            ->placeholder('Présentez brièvement cet utilisateur...')
                            ->rows(5)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Select::make('intention')
                            ->label('Intention')
                            ->options([
                                'relationship' => 'Relation sérieuse',
                                'dating' => 'Rencontre',
                                'friendship' => 'Amitié',
                                'networking' => 'Networking',
                            ])
                            ->native(false),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | Localisation
                |--------------------------------------------------------------------------
                */

                Section::make('Localisation')
                    ->description('Informations géographiques du profil.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([

                        TextInput::make('city')
                            ->label('Ville')
                            ->placeholder('Kinshasa')
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label('Pays')
                            ->placeholder('République démocratique du Congo')
                            ->maxLength(255),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Rôle & permissions
                |--------------------------------------------------------------------------
                */

                Section::make('Rôle & permissions')
                    ->description('Définissez les privilèges de cet utilisateur dans le backoffice.')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([

                        Select::make('role')
                            ->label('Rôle')
                            ->options([
                                'user' => 'Utilisateur',
                                'admin' => 'Administrateur',
                                'support' => 'Support',
                                'moderator' => 'Modérateur',
                            ])
                            ->default('user')
                            ->required()
                            ->native(false),

                        Toggle::make('is_staff')
                            ->label('Membre du staff')
                            ->default(false)
                            ->helperText(
                                'Permet de considérer cet utilisateur comme membre du personnel.'
                            ),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | Statut du profil
                |--------------------------------------------------------------------------
                */

                Section::make('Statut du profil')
                    ->description('Contrôlez la visibilité et la vérification du compte.')
                    ->icon('heroicon-o-check-badge')
                    ->columns(2)
                    ->schema([

                        Toggle::make('verified')
                            ->label('Profil vérifié')
                            ->default(false)
                            ->helperText(
                                'Affiche le badge de vérification sur le profil.'
                            ),

                        Toggle::make('is_visible')
                            ->label('Profil visible')
                            ->default(true)
                            ->helperText(
                                'Si désactivé, le profil ne sera plus visible dans Discover.'
                            ),

                        DateTimePicker::make('last_seen_at')
                            ->label('Dernière activité')
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('delete_reason')
                            ->label('Motif de suppression')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Aucun motif')
                            ->columnSpanFull(),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Récupération du mot de passe
                |--------------------------------------------------------------------------
                */

                Section::make('Récupération du mot de passe')
                    ->description('Informations techniques liées à la récupération du compte.')
                    ->icon('heroicon-o-key')
                    ->collapsed()
                    ->columns(2)
                    ->schema([

                        TextInput::make('password_reset_attempts')
                            ->label('Tentatives OTP')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('password_reset_requests')
                            ->label('Demandes de réinitialisation')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('password_reset_last_sent_at')
                            ->label('Dernier OTP envoyé')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),

                        DateTimePicker::make('password_reset_blocked_until')
                            ->label('Bloqué jusqu’au')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),

                        DateTimePicker::make('password_reset_otp_expires_at')
                            ->label('Expiration OTP')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),

                        TextInput::make('password_reset_otp')
                            ->label('OTP actuel')
                            ->password()
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
