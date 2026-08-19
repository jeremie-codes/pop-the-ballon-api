<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Informations générales
                |--------------------------------------------------------------------------
                */

                Section::make('Informations générales')
                    ->description('Informations principales de la campagne.')
                    ->icon('heroicon-o-megaphone')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->placeholder('Ex : Découvrez notre nouvelle offre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Décrivez la campagne...')
                            ->rows(4)
                            ->columnSpanFull(),

                        Select::make('campaign_type')
                            ->label('Type de campagne')
                            ->options([
                                'feature' => 'Feature',
                                'commercial' => 'Commercial',
                                'sponsored' => 'Sponsored',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('media_type')
                            ->label('Type de contenu')
                            ->options([
                                'image' => 'Image',
                                'carousel' => 'Carousel',
                                'video' => 'Vidéo',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'active' => 'Active',
                                'paused' => 'En pause',
                                'rejected' => 'Rejetée',
                                'expired' => 'Expirée',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),

                        TextInput::make('priority')
                            ->label('Priorité')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Plus la valeur est élevée, plus la campagne est prioritaire.'),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Propriétaire
                |--------------------------------------------------------------------------
                */

                Section::make('Propriétaire')
                    ->description('Détermine à qui appartient la campagne.')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Select::make('owner_type')
                            ->label('Type de propriétaire')
                            ->options([
                                'admin' => 'Admin',
                                'partner' => 'Partner',
                                'user' => 'User',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('owner_id')
                            ->label('ID du propriétaire')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('created_by')
                            ->label('Créée par (User ID)')
                            ->numeric()
                            ->nullable(),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Ciblage & bouton
                |--------------------------------------------------------------------------
                */

                Section::make('Ciblage & action')
                    ->description('Configure l’action effectuée lorsque l’utilisateur interagit avec la campagne.')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->columns(2)
                    ->schema([
                        TextInput::make('button_text')
                            ->label('Texte du bouton')
                            ->placeholder('Ex : Découvrir')
                            ->maxLength(255),

                        Select::make('target_type')
                            ->label('Type de destination')
                            ->options([
                                'url' => 'URL',
                                'feature' => 'Fonctionnalité',
                                'premium' => 'Premium',
                                'marketplace' => 'Marketplace',
                                'profile' => 'Profil',
                                'external' => 'Externe',
                            ])
                            ->native(false),

                        TextInput::make('target_value')
                            ->label('Destination')
                            ->placeholder('URL, ID, route, etc.')
                            ->columnSpanFull(),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Budget & tarification
                |--------------------------------------------------------------------------
                */

                Section::make('Budget & tarification')
                    ->description('Informations financières liées à la campagne.')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->schema([
                        TextInput::make('budget')
                            ->label('Budget')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),

                        TextInput::make('price')
                            ->label('Prix')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),

                        TextInput::make('views_count')
                            ->label('Vues')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        TextInput::make('clicks_count')
                            ->label('Clics')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Planification
                |--------------------------------------------------------------------------
                */

                Section::make('Planification')
                    ->description('Définissez la période pendant laquelle la campagne sera diffusée.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Date de début')
                            ->native(false),

                        DateTimePicker::make('end_at')
                            ->label('Date de fin')
                            ->native(false),
                    ]),
            ]);
    }
}
