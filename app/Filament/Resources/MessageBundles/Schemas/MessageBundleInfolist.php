<?php

namespace App\Filament\Resources\MessageBundles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageBundleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Informations du pack')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns(2)
                    ->schema([

                        TextEntry::make('title')
                            ->label('Nom du pack')
                            ->weight('bold'),

                        TextEntry::make('messages')
                            ->label('Messages')
                            ->suffix(' messages'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('Aucune description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Tarification')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->schema([

                        TextEntry::make('price')
                            ->label('Prix')
                            ->money('USD'),

                        TextEntry::make('equivalent')
                            ->label('Équivalent')
                            ->suffix(' CDF'),

                        TextEntry::make('currency')
                            ->label('Devise')
                            ->badge(),
                    ]),

                Section::make('Publication')
                    ->icon('heroicon-o-eye')
                    ->columns(2)
                    ->schema([

                        IconEntry::make('popular')
                            ->label('Pack populaire')
                            ->boolean(),

                        IconEntry::make('active')
                            ->label('Pack actif')
                            ->boolean(),
                    ]),

                Section::make('Informations système')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->schema([

                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Modifié le')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
