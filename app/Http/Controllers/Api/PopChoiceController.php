<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerPopChoiceRequest;
use App\Http\Resources\PopChoiceResource;
use App\Models\PopChoice;
use App\Models\PopChoiceSession;
use App\Services\PopChoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PopChoiceController extends Controller
{
    public function __construct(
        protected PopChoiceService $popChoiceService
    ) {}

    /**
     * Get the next automatic Pop Choice.
     *
     * GET /api/pop-choices/next
     */
    public function next(Request $request): JsonResponse
    {
        $popChoice = $this->popChoiceService
            ->getNextAutomatic($request->user());

        if (!$popChoice) {
            return response()->json([
                'data' => null,
                'message' => 'No Pop Choice available right now.',
            ]);
        }

        return response()->json([
            'data' => new PopChoiceResource($popChoice),
        ]);
    }

    /**
     * Start a voluntary Pop Choice session.
     *
     * POST /api/pop-choices/sessions
     */
    public function startSession(
        Request $request
    ): JsonResponse {
        $session = $this->popChoiceService
            ->startSession($request->user());

        return response()->json([
            'message' => 'Pop Choice session started.',
            'data' => [
                'id' => $session->id,
                'started_at' => $session->started_at,
                'completed_at' => $session->completed_at,
                'limit' => 5,
                'answered_count' => $session->answers()->count(),
            ],
        ]);
    }

    /**
     * Get the next question in a voluntary session.
     *
     * GET /api/pop-choices/sessions/{session}/next
     */
    public function nextForSession(
        Request $request,
        PopChoiceSession $session
    ): JsonResponse {
        try {
            $popChoice = $this->popChoiceService
                ->getNextForSession(
                    $request->user(),
                    $session
                );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }

        if (!$popChoice) {
            return response()->json([
                'data' => null,
                'message' => 'No more Pop Choices available for this session.',
            ]);
        }

        return response()->json([
            'data' => new PopChoiceResource($popChoice),
        ]);
    }

    /**
     * Answer a Pop Choice.
     *
     * POST /api/pop-choices/{popChoice}/answer
     */
    public function answer(
        AnswerPopChoiceRequest $request,
        PopChoice $popChoice
    ): JsonResponse {
        $session = null;

        /*
         * session_id is optional.
         *
         * null = automatic Pop Choice
         * value = voluntary session
         */
        if ($request->filled('session_id')) {
            $session = PopChoiceSession::query()
                ->find($request->integer('session_id'));

            if (!$session) {
                return response()->json([
                    'message' => 'Pop Choice session not found.',
                ], 404);
            }
        }

        try {
            $answer = $this->popChoiceService->answer(
                $request->user(),
                $popChoice,
                $request->string('answer')->toString(),
                $session
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Answer saved successfully.',
            'data' => [
                'id' => $answer->id,
                'pop_choice_id' => $answer->pop_choice_id,
                'pop_choice_session_id' => $answer->pop_choice_session_id,
                'answer' => $answer->answer,
                'answered_at' => $answer->answered_at,
            ],
        ]);
    }

    /**
     * Complete a voluntary session manually.
     *
     * POST /api/pop-choices/sessions/{session}/complete
     */
    public function completeSession(
        Request $request,
        PopChoiceSession $session
    ): JsonResponse {
        try {
            /*
             * getNextForSession() already verifies ownership,
             * but here we need the same protection.
             */
            if ($session->user_id !== $request->user()->id) {
                throw new RuntimeException(
                    'This Pop Choice session does not belong to you.'
                );
            }

            $session = $this->popChoiceService
                ->completeSession($session);

            return response()->json([
                'message' => 'Pop Choice session completed.',
                'data' => [
                    'id' => $session->id,
                    'started_at' => $session->started_at,
                    'completed_at' => $session->completed_at,
                    'answered_count' => $session->answers()->count(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
