<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BuilderController extends Controller
{
    public function generate(Request $request)
    {
        $prompt = $request->input('prompt');

        // Envia para OpenAI
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a front-end project generator using React, Vite, and Tailwind. Always respond with a valid JSON containing "project_name" and a "structure" key. "structure" must be a tree array of folders/files with name, type ("file" or "folder"), optional content, and children.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
            ]);

        $content = $response->json('choices.0.message.content');

        // Tenta decodificar
        $project = json_decode($content, true);

        // Falha no JSON
        if (!is_array($project) || !isset($project['structure'])) {
            Log::error('Invalid project structure from OpenAI', [
                'raw' => $content,
                'decoded' => $project,
            ]);

            return response()->json([
                'error' => 'Invalid response from AI. Could not parse project structure.',
            ], 422);
        }

        // Salva estrutura no disco
        $projectName = $project['project_name'] ?? 'generated-project';
        $this->saveStructure("projects/{$projectName}", $project['structure']);

        return response()->json([
            'project' => $project,
        ]);
    }

    private function saveStructure(string $basePath, array $structure)
    {
        foreach ($structure as $item) {
            $path = $basePath . '/' . $item['name'];

            if ($item['type'] === 'folder') {
                Storage::makeDirectory($path);

                if (!empty($item['children'])) {
                    $this->saveStructure($path, $item['children']);
                }
            } elseif ($item['type'] === 'file') {
                Storage::put($path, $item['content'] ?? '');
            }
        }
    }

    public function list()
    {
        $folders = collect(Storage::directories('projects'))
            ->map(fn ($dir) => basename($dir))
            ->values()
            ->all();

        return response()->json(['projects' => $folders]);
    }
}
