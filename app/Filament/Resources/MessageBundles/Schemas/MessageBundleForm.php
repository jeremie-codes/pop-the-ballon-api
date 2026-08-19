<?php

namespace App\Filament\Resources\MessageBundles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageBundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Informations du pack
                |--------------------------------------------------------------------------
                */

                Section::make('Informations du pack')
                    ->description('Définissez le nom et le contenu du pack de messages.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns(2)
                    ->schema([

                        TextInput::make('title')
                            ->label('Nom du pack')
                            ->placeholder('Ex : Pack Starter')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('messages')
                            ->label('Nombre de messages')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->suffix(' messages')
                            ->helperText('Nombre de messages inclus dans ce pack.'),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Décrivez ce que contient ce pack...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Tarification
                |--------------------------------------------------------------------------
                */

                Section::make('Tarification')
                    ->description('Configurez le prix du pack et son équivalent en francs congolais.')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(2)
                    ->schema([

                        TextInput::make('price')
                            ->label('Prix')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Prix du pack en dollars américains.'),

                        TextInput::make('equivalent')
                            ->label('Équivalent en CDF')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->suffix(' CDF')
                            ->step(1)
                            ->helperText('Équivalent du prix en francs congolais.'),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Publication
                |--------------------------------------------------------------------------
                */

                Section::make('Publication')
                    ->description('Contrôlez la visibilité et la mise en avant du pack.')
                    ->icon('heroicon-o-eye')
                    ->columns(2)
                    ->schema([

                        Toggle::make('popular')
                            ->label('Pack populaire')
                            ->helperText('Mettre ce pack en avant comme offre recommandée.')
                            ->default(false),

                        Toggle::make('active')
                            ->label('Pack actif')
                            ->helperText('Un pack actif est disponible à l’achat pour les utilisateurs.')
                            ->default(true),
                    ]),
            ]);
    }
}
