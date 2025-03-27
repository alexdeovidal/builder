<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
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

    public function save(Request $request, string $name)
    {
        $structure = $request->input('structure', []);

        if (!is_array($structure)) {
            return response()->json(['error' => 'Estrutura inválida'], 422);
        }

        $basePath = "projects/{$name}";

        // Remove projeto existente para recriar
        Storage::deleteDirectory($basePath);
        Storage::makeDirectory($basePath);

        $this->saveStructure($basePath, $structure);

        return response()->json(['message' => 'Projeto salvo com sucesso']);
    }

    public function downloadZip(string $name)
    {
        $folder = storage_path("app/projects/{$name}");
        $zipPath = storage_path("app/projects/{$name}.zip");

        if (!is_dir($folder)) {
            return response()->json(['error' => 'Projeto não encontrado'], 404);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = str_replace($folder . DIRECTORY_SEPARATOR, '', $filePath);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    public function load(string $name)
    {
        $path = "projects/{$name}";

        if (!Storage::exists($path)) {
            return response()->json(['error' => 'Projeto não encontrado.'], 404);
        }

        return response()->json([
            'project' => [
                'project_name' => $name,
                'structure' => $this->loadStructure($path),
            ]
        ]);
    }

    private function loadStructure(string $basePath)
    {
        $items = Storage::allFiles($basePath);
        $tree = [];

        foreach ($items as $file) {
            $relative = str_replace($basePath . '/', '', $file);
            $parts = explode('/', $relative);
            $this->addToTree($tree, $parts, Storage::get($file));
        }

        return $tree;
    }

    private function addToTree(array &$tree, array $parts, string $content)
    {
        $part = array_shift($parts);

        foreach ($tree as &$node) {
            if ($node['name'] === $part && $node['type'] === 'folder') {
                if ($parts) {
                    $this->addToTree($node['children'], $parts, $content);
                }
                return;
            }
        }

        if (empty($parts)) {
            $tree[] = [
                'name' => $part,
                'type' => 'file',
                'content' => $content,
            ];
        } else {
            $folder = [
                'name' => $part,
                'type' => 'folder',
                'children' => [],
            ];
            $tree[] = &$folder;
            $this->addToTree($folder['children'], $parts, $content);
        }
    }

}
