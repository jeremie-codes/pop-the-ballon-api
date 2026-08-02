<?php

namespace App\Filament\Resources\Conversations\Tables;

use App\Filament\Resources\Conversations\ConversationResource;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ConversationsTable
{

    public static function configure(Table $table): Table
    {

        return $table

            ->columns([
                TextColumn::make('client.full_name')
                    ->label('Utilisateur')
                    ->searchable(),

                TextColumn::make('lastMessage.body')
                    ->label('Dernier message')
                    ->limit(50),

                TextColumn::make('last_message_at')
                    ->dateTime()
                    ->label('Dernière activité'),

            ])
            ->recordActions([
                Action::make('chat')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(
                        fn($record) =>
                        ConversationResource::getUrl('chat', [
                            'record' => $record,
                        ])
                    ),
            ])
            ->toolbarActions([
                /*BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),*/
            ]);
    }
}
