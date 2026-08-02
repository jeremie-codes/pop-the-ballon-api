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
use App\Jobs\SendMessageNotification;
use Illuminate\Support\Facades\Storage;

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

    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json(
            Conversation::query()
                ->with(['userOne.photos', 'userTwo.photos', 'messages' => fn($query) => $query->latest()->limit(1)])
                ->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id)
                ->latest('last_message_at')
                ->get()
                ->map(fn(Conversation $conversation) => $this->conversationPayload($conversation, $user))
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
                message: $message,
                userId: $user->id,
                incrementUnread: false,
            );

            // Destinataire : mettre à jour son Inbox
            // et incrémenter unread
            ConversationUpdated::dispatch(
                message: $message,
                userId: $otherUser->id,
                incrementUnread: true,
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
                conversationId: $conversation->id,
                readerId: $user->id,
                messageIds: $messageIds
                    ->map(fn ($id) => (string) $id)
                    ->values()
                    ->all(),
            );

            // 4. L'utilisateur qui avait envoyé ces messages
            $senderId = $conversation->user_one_id === $user->id
                ? $conversation->user_two_id
                : $conversation->user_one_id;

            // 5. Informer son Inbox
            ConversationSeen::dispatch(
                conversationId: $conversation->id,
                senderId: $senderId,
                readerId: $user->id,
            );

            return response()->json([
                'success' => true,
                'message_ids' => $messageIds
                    ->map(fn ($id) => (string) $id)
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

        $last = $conversation->messages
            ->sortByDesc('created_at')
            ->first();

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

        $payload = [
            'id' => (string) $conversation->id,
            'profileId' => (string) $other->id,
            'name' => $other->displayName(),
            'verified' => (bool) $other->verified,
            'avatar' => optional($other->photos->first())->path ?? '',
            'is_staff' => (bool) $other->is_staff,
            'is_voice' => $last?->type === MessageType::VOICE,
            'is_video' => $last?->type === MessageType::VIDEO,
            'is_image' => $last?->type === MessageType::IMAGE,
            'message' => match ($last?->type?->value) {
                'video' => 'Vidéo',
                'image' => 'Image',
                'voice' => 'Message vocal',
                'text' => $last?->body ?? '',
                default => '',
            },
            'time' => optional($last?->created_at ?? $conversation->created_at)->toISOString(),
            'unread' => $unread,
            'read' => $read,
            'lastMessageAt' => optional($conversation->last_message_at)->toDateTimeString(),
            'matched' => true,
            'senderId' => $last? (string) $last->sender_id: null,
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
            'interests' => $profile->interests->pluck('name')->values(),
        ];
    }
}
