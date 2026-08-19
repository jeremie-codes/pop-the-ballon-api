<?php

namespace App\Filament\Resources\Conversations\Tables;

use App\Filament\Resources\Conversations\ConversationResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /*
                |--------------------------------------------------------------------------
                | Client
                |--------------------------------------------------------------------------
                */

                ImageColumn::make('client.avatar')
                    ->label('')
                    ->circular()
                    ->size(45)
                    ->defaultImageUrl(
                        asset('default-avatar.png')
                    ),

                TextColumn::make('client.full_name')
                    ->label('Utilisateur')
                    ->searchable([
                        'first_name',
                        'last_name',
                    ])
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn($record) => $record->client?->email
                    ),

                /*
                |--------------------------------------------------------------------------
                | Dernier message
                |--------------------------------------------------------------------------
                */

                TextColumn::make('lastMessage.body')
                    ->label('Dernier message')
                    ->limit(60)
                    ->placeholder('Pièce jointe')
                    ->description(
                        fn($record) => match ($record->lastMessage?->type?->value) {
                            'image' => '📷 Image',
                            'video' => '🎥 Vidéo',
                            default => null,
                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | Type
                |--------------------------------------------------------------------------
                */

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn(?string $state) => match ($state) {
                            'support' => 'Support',
                            'match' => 'Match',
                            default => ucfirst((string) $state),
                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | Dernière activité
                |--------------------------------------------------------------------------
                */

                TextColumn::make('last_message_at')
                    ->label('Dernière activité')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(
                        fn($record) => $record->last_message_at?->format('d/m/Y H:i')
                    ),

            ])

            ->filters([
                //
            ])

            ->recordActions([

                Action::make('chat')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->url(
                        fn($record) => ConversationResource::getUrl(
                            'chat',
                            ['record' => $record]
                        )
                    ),

            ])

            ->toolbarActions([])

            ->defaultSort('last_message_at', 'desc')

            ->striped(false);
    }
}
