<?php

namespace App\Http\Controllers\Api;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\SupportConversationService;
use Illuminate\Http\Request;

class SupportRequestController extends Controller
{
    public function conversation(
        Request $request,
        SupportConversationService $service
    ) {
        $conversation = $service->getOrCreate($request->user('sanctum'));

        return response()->json(
            $service->formatForMobile(
                $conversation->load(['messages.sender'])
            )
        );
    }

    public function storeRequestClient(Request $request, SupportConversationService $service)
    {
        try {
            $data = $request->validate([
                'message' => ['required', 'string'],
            ]);

            $conversation = $service->getOrCreate($request->user('sanctum'));

            $conversation->messages()->create([
                'sender_id' => $request->user('sanctum')->id,
                'type' => MessageType::TEXT,
                'body' => $data['message'],
            ]);

            return response()->json(
                $service->formatForMobile(
                    $conversation->load(['messages.sender'])
                )
            );
        } catch (\Throwable $e) {
            logger()->error('storeAction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur interne', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'type' => ['required', 'in:help,complaint,review'],
                'subject' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string'],
                'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            ]);

            SupportRequest::query()->create([
                'user_id' => $request->user()?->id,
                ...$data,
            ]);

            return response()->json(['success' => true, 'message' => 'Message envoyé.'], 201);
        } catch (\Throwable $e) {
            logger()->error('storeAction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erreur interne', 'error' => $e->getMessage()], 500);
        }
    }
}
