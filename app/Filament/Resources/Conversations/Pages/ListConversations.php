<?php

namespace App\Filament\Resources\Conversations\Pages;

use App\Filament\Resources\Conversations\ConversationResource;
use App\Models\Message;
use App\Models\User;
use App\Services\SupportConversationService;
use Filament\Actions\Action;
//use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;

class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('newSupport')
                ->label('Nouvelle conversation')
                ->icon('heroicon-o-plus')
                ->form([

                    Select::make('user_id')
                        ->label('Utilisateur')
                        ->options(
                            User::where('is_staff', false)
                                ->get()
                                ->pluck('full_name', 'id')
                        )
                        ->searchable()
                        ->required(),

                    Textarea::make('message')
                        ->label('Message')
                        ->required(),

                ])

                ->action(function (array $data) {

                    $service = app(SupportConversationService::class);
                   
                    $user = User::findOrFail($data['user_id']);

                    $conversation = $service->getOrCreate($user);
                    
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => auth()->id(),
                        'type' => 'text',
                        'body' => $data['message'],
                        'is_broadcast' => false,
                    ]);

                    $conversation->update([
                        'last_message_at' => now()
                    ]);

                    return redirect(
                        ConversationResource::getUrl('chat', [
                            'record' => $conversation->id,
                        ])
                    );
                }),


        ];
    }
}
