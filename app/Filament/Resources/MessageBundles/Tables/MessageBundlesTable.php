<?php

namespace App\Filament\Resources\MessageBundles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageBundlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /*
                |--------------------------------------------------------------------------
                | Pack
                |--------------------------------------------------------------------------
                */

                TextColumn::make('title')
                    ->label('Pack')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn($record) => $record->description
                    ),

                /*
                |--------------------------------------------------------------------------
                | Messages
                |--------------------------------------------------------------------------
                */

                TextColumn::make('messages')
                    ->label('Messages')
                    ->numeric()
                    ->sortable()
                    ->suffix(' messages'),

                /*
                |--------------------------------------------------------------------------
                | Prix
                |--------------------------------------------------------------------------
                */

                TextColumn::make('price')
                    ->label('Prix')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('equivalent')
                    ->label('Équivalent')
                    ->numeric()
                    ->sortable()
                    ->suffix(' CDF'),

                /*
                |--------------------------------------------------------------------------
                | Devise
                |--------------------------------------------------------------------------
                */

                TextColumn::make('currency')
                    ->label('Devise')
                    ->badge()
                    ->sortable(),

                /*
                |--------------------------------------------------------------------------
                | Popularité
                |--------------------------------------------------------------------------
                */

                IconColumn::make('popular')
                    ->label('Populaire')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),

                /*
                |--------------------------------------------------------------------------
                | Statut
                |--------------------------------------------------------------------------
                */

                IconColumn::make('active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                //
            ])

            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('messages', 'asc');
    }
}
