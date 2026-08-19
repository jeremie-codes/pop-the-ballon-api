<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /*
                |--------------------------------------------------------------------------
                | Utilisateur
                |--------------------------------------------------------------------------
                */

                ImageColumn::make('avatar')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->size(45)
                    ->defaultImageUrl(
                        asset('default-avatar.png')
                    ),

                TextColumn::make('first_name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn($state, $record) =>
                        trim(
                            $record->first_name . ' ' .
                                $record->last_name
                        )
                    )
                    ->description(
                        fn($record) => '@' . $record->username
                    )
                    ->weight('bold'),

                /*
                |--------------------------------------------------------------------------
                | Contact
                |--------------------------------------------------------------------------
                */

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | Rôle
                |--------------------------------------------------------------------------
                */

                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(
                        fn(?string $state) => match ($state) {
                            'user' => 'Utilisateur',
                            'admin' => 'Administrateur',
                            'support' => 'Support',
                            'moderator' => 'Modérateur',
                            default => ucfirst((string) $state),
                        }
                    )
                    ->sortable(),

                /*
                |--------------------------------------------------------------------------
                | Vérification
                |--------------------------------------------------------------------------
                */

                IconColumn::make('verified')
                    ->label('Vérifié')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus'),

                /*
                |--------------------------------------------------------------------------
                | Visibilité
                |--------------------------------------------------------------------------
                */

                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                /*
                |--------------------------------------------------------------------------
                | Staff
                |--------------------------------------------------------------------------
                */

                IconColumn::make('is_staff')
                    ->label('Staff')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-minus'),

                /*
                |--------------------------------------------------------------------------
                | Activité
                |--------------------------------------------------------------------------
                */

                TextColumn::make('last_seen_at')
                    ->label('Dernière activité')
                    ->since()
                    ->sortable()
                    ->description(
                        fn($record) =>
                        $record->last_seen_at?->format('d/m/Y H:i')
                    ),

                /*
                |--------------------------------------------------------------------------
                | Création
                |--------------------------------------------------------------------------
                */

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                /*
                |--------------------------------------------------------------------------
                | Informations secondaires
                |--------------------------------------------------------------------------
                */

                TextColumn::make('birth_date')
                    ->label('Date de naissance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender')
                    ->label('Genre')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('country')
                    ->label('Pays')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('intention')
                    ->label('Intention')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email_verified_at')
                    ->label('Email vérifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delete_reason')
                    ->label('Motif de suppression')
                    ->toggleable(isToggledHiddenByDefault: true),
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
