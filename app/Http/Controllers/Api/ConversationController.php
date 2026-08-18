<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Events\ConversationUpdated;
use App\Events\MessageSeen;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MatchModel;
use App\Models\Message;
use App\Models\MessageCredit;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\ExpoNotificationService;
use Illuminate\Http\Request;
use App\Events\ConversationSeen;
use App\Enums\MessageType;
use App\Services\SupportConversationService;

class ConversationController extends Controller
{
    public function matches(Request $request)
    {
        $user = $request->user();
        $matches = MatchModel::query()
            ->with(['userOne.photos', 'userOne.interests', 'userTwo.photos', 'userTwo.interests'])
            ->where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->latest('matched_at')
            ->get()
            ->map(function (MatchModel $match) use ($user) {
                $profile = $match->user_one_id === $user->id ? $match->userTwo : $match->userOne;
                $conversation = Conversation::query()
                    ->where(function ($query) use ($user, $profile) {
                        $query
                            ->where('user_one_id', $user->id)
                            ->where('user_two_id', $profile->id);
                    })
                    ->orWhere(function ($query) use ($user, $profile) {
                        $query
                            ->where('user_one_id', $profile->id)
                            ->where('user_two_id', $user->id);
                    })
                    ->first();

                return $this->profilePayload($profile, $conversation);
            });

        return response()->json($matches);
    }

    public function index(
        Request $request,
        SupportConversationService $supportService
    ) {
        $user = $request->user('sanctum');

        $support = $supportService->getSupportUser();

        return response()->json(
            Conversation::query()
                ->with([
                    'userOne.photos',
                    'userTwo.photos',
                    'messages' => fn($query) => $query->latest(),
                ])
                ->where(function ($query) use ($user) {
                    $query
                        ->where('user_one_id', $user->id)
                        ->orWhere('user_two_id', $user->id);
                })
                ->where(function ($query) use ($support) {

                    // Conversations normales
                    $query->where('type', '!=', 'support')

                        // Conversations support :
                        // seulement si le support a répondu
                        ->orWhere(function ($query) use ($support) {
                            $query
                                ->where('type', 'support')
                                ->whereHas('messages', function ($messageQuery) use ($support) {
                                    $messageQuery->where(
                                        'sender_id',
                                        $support->id
                                    );
                                });
                        });
                })
                ->latest('last_message_at')
                ->get()
                ->map(
                    fn(Conversation $conversation) =>
                    $this->conversationPayload($conversation, $user)
                )
                ->values()
        );
    }


    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (! in_array($user->id, [$conversation->user_one_id, $conversation->user_two_id], true)) {
            return response()->json(['message' => 'Conversation introuvable.'], 404);
        }

        $conversation->load(['userOne.photos', 'userTwo.photos', 'messages' => fn($query) => $query->oldest()]);

        return response()->json($this->conversationPayload($conversation, $user, true));
    }

    public function storeMessage(Request $request, Conversation $conversation, ExpoNotificationService $expo)
    {
        try {
            $user = $request->user('sanctum');

            if (! in_array($user->id, [$conversation->user_one_id, $conversation->user_two_id], true)) {
                return response()->json(['message' => 'Conversation introuvable.'], 404);
            }

            $credit = MessageCredit::where(
                'user_id',
                $user->id
            )->first();

            if (
                !$credit ||
                $credit->available_messages <= 0
            ) {
                return response()->json([
                    'message' => 'no_available_messages',
                ], 403);
            }

            $data = $request->validate([
                'type' => ['nullable', 'in:text,voice'],
                'body' => ['nullable', 'string', 'max:5000'],
                'voice' => ['nullable', 'file', 'mimes:m4a,mp4,mp3,wav,ogg', 'max:10240'],
                'duration' => ['nullable', 'integer', 'min:1'],
                'client_id' => ['required', 'string'],
            ]);

            $type = MessageType::from($data['type'] ?? 'text');

            if ($type === MessageType::TEXT) {
                if (empty($data['body'])) {
                    return response()->json([
                        'message' => 'Le texte est obligatoire.'
                    ], 422);
                }

                $message = $conversation->messages()->create([
                    'sender_id' => $user->id,
                    'type' => MessageType::TEXT,
                    'body' => $data['body'],
                ]);
            } else {

                if (!$request->hasFile('voice')) {
                    return response()->json([
                        'message' => 'Le fichier audio est obligatoire.'
                    ], 422);
                }

                $file = $request->file('voice');

                $path = $file->store('messages/voices', 'public');

                $message = $conversation->messages()->create([
                    'sender_id' => $user->id,
                    'type' => MessageType::VOICE,
                    'body' => null,
                    'attachment' => $path,
                    'attachment_duration' => $data['duration'] ?? null,
                    'attachment_size' => $file->getSize(),
                    'attachment_mime' => $file->getMimeType(),
                ]);
            }

            $conversation->forceFill(['last_message_at' => now()])->save();
            $otherUser = $conversation->user_one_id === $user->id ? $conversation->userTwo : $conversation->userOne;

            // Diffusion temps réel et le client_id est utilisé pour identifier le message côté client sender et éviter les doublons
            MessageCreated::dispatch($message,  $data['client_id']);

            // Expéditeur : mettre à jour son Inbox,
            // mais ne pas incrémenter unread
            ConversationUpdated::dispatch(
                $message,
                $user->id,
                false
            );

            // Destinataire : mettre à jour son Inbox
            // et incrémenter unread
            ConversationUpdated::dispatch(
                $message,
                $otherUser->id,
                true,
            );

            $notificationBody = $message->type === MessageType::VOICE
                ? $user->displayName() . ' a envoyé une note vocale 🎤'
                : $user->displayName() . ': ' . $message->body;


            foreach ($otherUser->devices as $device) {
                $expo->send(
                    $device->expo_token,
                    '🎈PopTheBallon - Nouveau message',
                    $notificationBody,
                    [
                        'type' => 'message',
                        'user_id' => $user->id,
                        'conversation_id' => $conversation->id,
                        'url' => '/(app)/conversation/' . $conversation->id
                    ]
                );
            }

            $credit->decrement(
                'available_messages'
            );

            return response()->json([
                'id' => (string) $message->id,
                'type' => $message->type->value,
                'text' => $message->body,
                'attachment' => $message->attachment ?? null,
                'duration' => $message->attachment_duration,
                'time' => $message->created_at->format('H:i'),
                'mine' => true,
                'read' => false,
            ], 201);
        } catch (\Throwable $e) {
            logger()->error('storeMessageAction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur lors de l\'envoi du message.', 'error' => $e->getMessage()], 500);
        }
    }

    public function markAsRead(
        Request $request,
        Conversation $conversation
    ) {
        try {
            $user = $request->user('sanctum');

            if (! in_array(
                $user->id,
                [
                    $conversation->user_one_id,
                    $conversation->user_two_id,
                ],
                true
            )) {
                return response()->json([
                    'message' => 'Conversation introuvable.',
                ], 404);
            }

            // 1. Récupérer les messages reçus et encore non lus
            $messageIds = $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->pluck('id');

            if ($messageIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message_ids' => [],
                ]);
            }

            // 2. Marquer réellement les messages comme lus en base
            $conversation->messages()
                ->whereIn('id', $messageIds)
                ->update([
                    'read_at' => now(),
                ]);

            // 3. Informer la page de conversation
            MessageSeen::dispatch(
                $conversation->id,
                $user->id,
                $messageIds
                    ->map(fn($id) => (string) $id)
                    ->values()
                    ->all(),
            );

            // 4. L'utilisateur qui avait envoyé ces messages
            $senderId = $conversation->user_one_id === $user->id
                ? $conversation->user_two_id
                : $conversation->user_one_id;

            // 5. Informer son Inbox
            ConversationSeen::dispatch(
                $conversation->id,
                $senderId,
                $user->id,
            );

            // 5. Informer le Inbox de l'autre utilisateur
            ConversationSeen::dispatch(
                $conversation->id,
                $user->id,
                $senderId,
            );

            return response()->json([
                'success' => true,
                'message_ids' => $messageIds
                    ->map(fn($id) => (string) $id)
                    ->values(),
            ]);
        } catch (\Throwable $e) {
            logger()->error('markConversationRead failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur interne.',
            ], 500);
        }
    }

    private function conversationPayload(
        Conversation $conversation,
        User $viewer,
        bool $withMessages = false
    ): array {

        $other = $conversation->user_one_id === $viewer->id
            ? $conversation->userTwo
            : $conversation->userOne;

        if ($viewer->is_staff !== $other->is_staff) {
            $last = $conversation->messages()
                ->where('sender_id', $other->id)
                ->latest()
                ->first();
        } else {
            $last = $conversation->messages()
                ->latest()
                ->first();
        }

        $unread = $conversation->messages
            ->filter(
                fn(Message $message) =>
                $message->sender_id !== $viewer->id
                    && $message->read_at === null
            )
            ->count();

        // Le dernier message a-t-il été lu par le destinataire ?
        $read = $last !== null
            && $last->sender_id === $viewer->id
            && $last->read_at !== null;

        $lastCreatedAt = $last ? $last->created_at : $conversation->created_at;
        $lastMessageAt = $last ? $last->created_at : $conversation->last_message_at;

        $payload = [
            'id' => (string) $conversation->id,
            'profileId' => (string) $other->id,
            'name' => $other->displayName(),
            'verified' => (bool) $other->verified,
            'avatar' => optional($other->photos->first())->path ?? '',
            'is_staff' => (bool) $other->is_staff,
            'is_voice' => $last !== null && $last->type === MessageType::VOICE,
            'is_video' => $last !== null && $last->type === MessageType::VIDEO,
            'is_image' => $last !== null && $last->type === MessageType::IMAGE,
            'message' => $last !== null ? (function () use ($last) {
                switch ($last->type->value) {
                    case 'video':
                        return 'Vidéo';
                    case 'image':
                        return 'Image';
                    case 'voice':
                        return 'Message vocal';
                    case 'text':
                        return $last->body ?? '';
                    default:
                        return '';
                }
            })() : '',
            'time' => $lastCreatedAt ? $lastCreatedAt->toISOString() : null,
            'unread' => $unread,
            'read' => $read,
            'lastMessageAt' => $lastMessageAt ? $lastMessageAt->toDateTimeString() : null,
            'matched' => true,
            'senderId' => $last ? (string) $last->sender_id : null,
        ];

        if ($withMessages) {
            $payload['messages'] = $conversation->messages
                ->map(fn(Message $message) => [
                    'id' => (string) $message->id,
                    'type' => $message->type->value,
                    'text' => $message->body,
                    'attachment' => $message->attachment ?? null,
                    'duration' => $message->attachment_duration,
                    'sender_id' => (string) $message->sender_id,
                    'time' => $message->created_at->format('H:i'),
                    'mine' => $message->sender_id === $viewer->id,
                    'read' => $message->read_at !== null,
                    'createdAt' => $message->created_at->toISOString(),
                ])
                ->values();
        }

        return $payload;
    }

    private function profilePayload(User $profile, ?Conversation $conversation = null): array
    {
        return [
            'id' => (string) $profile->id,
            'conversationId' => $conversation ? (string) $conversation->id : null,
            'name' => $profile->displayName(),
            'age' => $profile->age() ?? 18,
            'city' => $profile->city ?? '',
            'country' => $profile->country ?? '',
            'bio' => $profile->bio ?? '',
            'intention' => $profile->intention ?? '',
            'verified' => (bool) $profile->verified,
            'distance' => '0 km',
            'pictures' => $profile->photos->map(fn(ProfilePhoto $photo) => ['name' => $photo->path])->values(),
            'avatar' => optional($profile->photos->first())->path ?? null,
            'lastSeen' => optional($profile->last_seen_at)->toDateTimeString(),
            'interests' => $profile->interests->pluck('name')->values(),
        ];
    }
}
