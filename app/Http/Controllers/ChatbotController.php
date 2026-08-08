<?php

namespace App\Http\Controllers;

use App\Services\ChatbotResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function respond(Request $request, ChatbotResponder $responder): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json([
            'reply' => $responder->reply($data['message']),
        ]);
    }
}
