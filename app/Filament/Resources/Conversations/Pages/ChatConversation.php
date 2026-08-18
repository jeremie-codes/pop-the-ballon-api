<?php

namespace App\Filament\Resources\Conversations\Pages;

use App\Events\ConversationUpdated;
use App\Events\MessageCreated;
use App\Filament\Resources\Conversations\ConversationResource;
use App\Models\Message;
use App\Services\ExpoNotificationService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ChatConversation extends Page
{
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ConversationResource::class;

    protected string $view = 'filament.resources.conversations.pages.chat-conversation';


    public string $message = '';

    public $attachment = null;



    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
    }



    public function sendMessage(): void
    {
        $this->validate([
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,webm',
                'max:102400', // 100 Mo
            ],
            'message' => [
                'nullable',
                'string',
            ],
        ]);

        if (blank($this->message) && !$this->attachment) {
            return;
        }

        $type = 'text';
        $attachmentPath = null;
        $attachmentMime = null;

        //dd($this->attachment);

        if ($this->attachment) {
            $attachmentMime = $this->attachment->getMimeType();

            if (str_starts_with($attachmentMime, 'image')) {
                $type = 'image';
            } elseif (str_starts_with($attachmentMime, 'video')) {
                $type = 'video';
            }

            $attachmentPath = $this->attachment->store(
                'messages',
                'public'
            );
        }

        $user = Auth::user();

        abort_unless(
            $this->record->type === 'support',
            403,
            'Cette conversation n\'est pas une conversation support.'
        );

        abort_unless(
            $user->is_staff && $user->role === 'support',
            403,
            'Vous n\'êtes pas autorisé à répondre aux conversations support.'
        );

        $message = Message::create([
            'conversation_id' => $this->record->id,
            'sender_id' => $user->id,
            'type' => $type,
            'body' => $this->message ?: null,
            'attachment' => $attachmentPath,
            'attachment_mime' => $attachmentMime,
            'is_broadcast' => false,
        ]);

        $this->record->update([
            'last_message_at' => now(),
        ]);

        // reset
        $this->message = '';
        $this->attachment = null;

        $otherUser = $this->record->user_one_id === $user->id ? $this->record->userTwo : $this->record->userOne;
        //$conversation->user_one_id === $user->id ? $conversation->userTwo : $conversation->userOne
        $expo = app(ExpoNotificationService::class);

        // Diffusion temps réel et le client_id est utilisé pour identifier le message côté client sender on le met null parce que le sender est sur web comme support donc pas necessaire
        MessageCreated::dispatch($message, null);

        // Destinataire : mettre à jour son Inbox
        // et incrémenter unread
        ConversationUpdated::dispatch(
            $message,
            $otherUser->id,
            true,
        );

        foreach ($otherUser->devices as $device) {
            $expo->send(
                $device->expo_token,
                '🎈PopTheBallon - Nouveau message',
                $message->body,
                [
                    'type' => 'message',
                    'user_id' => $user->id,
                ]
            );
        }
    }
}
