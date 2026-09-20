<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PopChoiceCompatibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PopChoiceCompatibilityController extends Controller
{
    public function __construct(
        private readonly PopChoiceCompatibilityService $compatibilityService,
    ) {}

    /**
     * GET /api/matches/{match}/compatibility
     */
    public function show(Request $request, User $match): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->compatibilityService->calculate(
                    $request->user(),
                    $match,
                ),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        }
    }
}
