<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\GitHubDeployer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BuilderController extends Controller
{
    // ✅ LISTAR PROJETOS
    public function index()
    {
        $userProjects = Project::where('user_id', auth()->id())->pluck('name');

        return response()->json([
            'projects' => $userProjects
        ]);
    }

    // ✅ GERAR NOVO PROJETO COM OPENAI
    public function generate(Request $request)
    {
        $prompt = $request->input('prompt');

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

        $project = json_decode($content, true);

        if (!is_array($project) || !isset($project['structure'])) {
            Log::error('Invalid project structure from OpenAI', [
                'raw' => $content,
                'decoded' => $project,
            ]);

            return response()->json([
                'error' => 'Invalid response from AI. Could not parse project structure.',
            ], 422);
        }

        $projectName = $project['project_name'] ?? 'generated-project';
        $this->saveStructure("projects/{$projectName}", $project['structure']);
        Project::updateOrCreate(
            ['name' => $projectName, 'user_id' => Auth::id()],
            ['name' => $projectName]
        );

        return response()->json([
            'project' => $project
        ]);
    }

    // ✅ SALVAR ESTRUTURA NO DISCO
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

    // ✅ BAIXAR .ZIP DO PROJETO
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

    // ✅ CARREGAR UM PROJETO EXISTENTE
    public function load(string $name)
    {

        $path = "projects/{$name}";

        if (!Storage::exists($path)) {
            return response()->json(['error' => 'Projeto não encontrado.'], 404);
        }

        try {
            return response()->json([
                'project' => [
                    'project_name' => $name,
                    'structure' => $this->loadStructure($path),
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar projeto', [
                'name' => $name,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Erro ao carregar o projeto.'], 500);
        }
    }


    private function loadStructure(string $basePath)
    {

        if (!Storage::exists($basePath)) {
            return [];
        }

        $items = Storage::allFiles($basePath);

        if (empty($items)) {
            return [];
        }

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

            if ($node['name'] === $part && $node['type'] === 'folder' && $part !== 'node_modules') {
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

    public function deployToGitHub(string $projectName)
    {
        $user = Auth::user();

        $project = Project::where('name', $projectName)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $repoUrl = app(GitHubDeployer::class)->deploy(
            $projectName,
            storage_path("app/projects/{$projectName}")
        );

        $project->update(['repo_url' => $repoUrl]);

        return response()->json(['repo_url' => $repoUrl]);
    }


    // ✅ OPCIONAL: SALVAR ATUALIZAÇÃO MANUAL DE UM PROJETO
    public function save(Request $request, string $name)
    {
        $structure = $request->input('structure', []);

        if (!is_array($structure)) {
            return response()->json(['error' => 'Estrutura inválida'], 422);
        }

        $basePath = "projects/{$name}";

        Storage::deleteDirectory($basePath);
        Storage::makeDirectory($basePath);

        $this->saveStructure($basePath, $structure);

        return response()->json(['message' => 'Projeto salvo com sucesso']);
    }
}
