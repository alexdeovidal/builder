<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class OpenAIController extends Controller
{
    public function __invoke(Request $request, OpenAIService $openAI)
    {
        $message = $request->input('message');

        if (!$message) {
            return response()->json(['error' => 'Mensagem não enviada'], 422);
        }

        $response = $openAI->chat($message);

        return response()->json(['reply' => $response]);
    }
}
