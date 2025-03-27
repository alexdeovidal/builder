<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    public function chat(string $message, string $model = 'gpt-3.5-turbo'): ?string
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $message]
                ],
            ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        // Log ou exception personalizada
        logger()->error('Erro ao conectar com OpenAI', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }
}
