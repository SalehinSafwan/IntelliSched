<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $chatbot)
    {
    }


    public function ask(Request $request): JsonResponse
    {
        $max = (int) config('intellisched.chatbot.max_length', 200);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:' . $max],
        ], [
            'question.required' => 'Please type a question.',
            'question.max' => "Please keep the question under {$max} characters.",
        ]);

        return response()->json($this->chatbot->ask($request->user(), $data['question']));
    }


    public function questions(Request $request): JsonResponse
    {
        return response()->json($this->chatbot->catalog($request->user()));
    }
}
