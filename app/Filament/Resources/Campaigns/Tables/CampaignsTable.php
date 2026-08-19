<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Campagne')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->campaign_type),

                TextColumn::make('owner_type')
                    ->label('Propriétaire')
                    ->badge()
                    ->sortable(),

                TextColumn::make('media_type')
                    ->label('Contenu')
                    ->badge()
                    ->formatStateUsing(
                        fn(?string $state) => match ($state) {
                            'image' => 'Image',
                            'carousel' => 'Carousel',
                            'video' => 'Vidéo',
                            default => $state,
                        }
                    ),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(
                        fn(?string $state) => match ($state) {
                            'draft' => 'Brouillon',
                            'pending' => 'En attente',
                            'approved' => 'Approuvée',
                            'active' => 'Active',
                            'paused' => 'En pause',
                            'rejected' => 'Rejetée',
                            'expired' => 'Expirée',
                            default => $state,
                        }
                    ),

                TextColumn::make('priority')
                    ->label('Priorité')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('views_count')
                    ->label('Vues')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('clicks_count')
                    ->label('Clics')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('budget')
                    ->label('Budget')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Prix')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('start_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])

            ->filters([
                //
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
