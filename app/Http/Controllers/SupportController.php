<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupportConversationService;


class SupportController extends Controller
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
}
