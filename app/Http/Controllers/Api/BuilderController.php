<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Project;
use App\Services\GitHubDeployer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Symfony\Component\Process\Process;

class BuilderController extends Controller
{
    // ✅ LISTAR PROJETOS
    public function index()
    {
        $projects = Project::where('user_id', Auth::id())
            ->get(['name', 'repo_url']); // Inclui repo_url

        return response()->json([
            'projects' => $projects
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
                        'content' => <<<EOT
You are a professional front-end project generator.

You must return a complete, production-ready React + Vite + Tailwind project in JSON format.

📁 Folder structure must follow React/Vite best practices, including:
- `src/components` — reusable components (PascalCase files)
- `src/pages` — page-level components like `AboutPage.tsx`, `ContactPage.tsx`
- `src/hooks` — custom hooks in camelCase (e.g. `useAuth.ts`)
- `src/assets` — images, logos, static files
- `src/styles` — global or base CSS files
- `public/` — favicon, robots.txt, etc.

🧱 Technologies:
- React + Vite
- TailwindCSS
- TypeScript (preferred)
- Heroicons or similar icons
- Google Fonts (via `<link>` in `index.html`)

📦 Required files:
- `index.html`, `main.tsx`, `App.tsx`, `vite.config.ts`
- Tailwind setup: `tailwind.config.js`, `postcss.config.js`, and `src/styles/tailwind.css`
- A valid `package.json` with scripts: `dev`, `build`, `preview`

🎯 Output format:
Only return raw JSON. Do not explain or describe anything before or after the JSON. No markdown, no comments, no extra text.
Return only valid JSON with this structure:

```json
{
  "project_name": "my-project-name",
  "structure": [
    {
      "name": "folder-or-file-name",
      "type": "file" | "folder",
      "content": "...", // for files
      "children": [ ... ] // for folders
    }
  ]
}

EOT

                        ,
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
            ]);

        $content = $response->json('choices.0.message.content');
        // Remove blocos markdown como ```json e ``` (caso existam)
        $content = preg_replace('/^```json|```$/m', '', trim($content));

        // Tenta extrair o JSON da resposta com regex
        preg_match('/\{.*\}/s', $content, $matches);

// Se encontrou o JSON, decodifica
        if (!empty($matches)) {
            $json = $matches[0];
            $project = json_decode($json, true);
        } else {
            $project = null;
        }

        Log::info('🧠 RAW AI RESPONSE', ['response' => $content]);

       // $project = json_decode($content, true);

        if (!is_array($project) || !isset($project['structure'])) {
            Log::error('Invalid project structure from OpenAI', [
                'raw' => $content,
                'decoded' => $project,
            ]);

            return response()->json([
                'error' => 'Invalid response from AI. Could not parse project structure.',
            ], 422);
        }

        //$projectName = $project['project_name'] ? $project['project_name'] : 'generated-project';
        $projectName = $project['project_name'] ? Str::slug($project['project_name']) : 'generated-project';
        $this->saveStructure("projects/{$projectName}", $project['structure']);
        Project::updateOrCreate(
            ['name' => $projectName, 'user_id' => Auth::id()],
            ['name' => $projectName]
        );

        return response()->json([
            'project' => $project
        ]);
    }

    public function extend(Request $request, string $projectName)
    {
        $prompt = $request->input('prompt');

        if (!$prompt) {
            return response()->json(['error' => 'Prompt é obrigatório.'], 422);
        }

        $project = Project::where('name', $projectName)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $prompt = $request->input('prompt');

        $structurePath = "projects/{$project->name}";
        $existingStructure = $this->loadStructure($structurePath);

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<EOT
You are a front-end assistant specialized in React, Vite, and TailwindCSS.

You will receive an existing project structure and a new feature request.
Your job is to return ONLY the new files and folders needed to extend the project.

✅ Follow these conventions:
- Folder names: **kebab-case** (e.g., `property-list-page`)
- File names: **PascalCase** (e.g., `PropertyListPage.tsx`, `AboutPage.tsx`)
- Place pages in `src/pages`, components in `src/components`, utils in `src/utils`, etc.
- Do not include `node_modules` or test files
- Files must contain meaningful, working code
- NEVER return empty files or folders
- Use TailwindCSS for styling where needed
- Format and indent all code properly
Only return raw JSON. Do not explain or describe anything before or after the JSON. No markdown, no comments, no extra text.

⚠️ Response format (strict JSON array):
[
  {
    "name": "folder-name",
    "type": "folder",
    "children": [
      {
        "name": "FileName.tsx",
        "type": "file",
        "content": "// valid, formatted code here"
      }
    ]
  }
]

🚫 Do not explain anything.
Only return the JSON array of new files/folders with actual code inside.

EOT

                        ,
                    ],
                    [
                        'role' => 'user',
                        'content' => "Estrutura existente:\n" . json_encode($existingStructure),
                    ],
                    [
                        'role' => 'user',
                        'content' => "Agora adicione ao projeto:\n{$prompt}",
                    ],
                ],
            ]);

        $newStructure = json_decode($response->json('choices.0.message.content'), true);

        if (!is_array($newStructure)) {
            return response()->json(['error' => 'Resposta inválida da IA'], 422);
        }

        $this->saveStructure($structurePath, $newStructure);

        return response()->json([
            'message' => 'Projeto atualizado com sucesso',
            'new_files' => $newStructure
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

                $ext = pathinfo($item['name'], PATHINFO_EXTENSION);
                $formatted = $this->formatCodeWithPrettier($item['content'] ?? '', $ext);
                Storage::put($path, $formatted);

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

        $items = collect(Storage::allFiles($basePath))
            ->reject(fn($file) =>
                str_contains($file, '/.git/') ||
                str_contains($file, '/node_modules/') ||
                str_contains($file, '.DS_Store')
            )
            ->values()
            ->all();

        if (empty($items)) {
            return [];
        }

        $tree = [];

        foreach ($items as $file) {


            $relative = str_replace($basePath . '/', '', $file);
            $parts = explode('/', $relative);
            try {
                $content = Storage::get($file);
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
            } catch (\Throwable $e) {
                \Log::error('Erro ao ler arquivo com encoding inválido', [
                    'file' => $file,
                    'exception' => $e->getMessage(),
                ]);
                $content = "// [Error reading file: encoding issue]";
            }

            $this->addToTree($tree, $parts, $content);
          //  $this->addToTree($tree, $parts, Storage::get($file));
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

        if ($project->repo_url) {
            return response()->json(['message' => 'Projeto já enviado para o GitHub.', 'repo_url' => $project->repo_url]);
        }

        $path = storage_path("app/private/projects/{$projectName}");
        if (!is_dir($path)) {
            return response()->json(['error' => 'Diretório do projeto não encontrado.'], 404);
        }

        $repoUrl = app(GitHubDeployer::class)->deploy($projectName, $path);

        $project->update(['repo_url' => $repoUrl]);

        return response()->json(['repo_url' => $repoUrl]);
    }

    function formatCodeWithPrettier(string $code, string $extension = 'js'): string
    {
        $ext = strtolower($extension);

        // Defina o parser com base na extensão
        $parserMap = [
            'js' => 'babel',
            'jsx' => 'babel',
            'ts' => 'typescript',
            'tsx' => 'typescript',
            'json' => 'json',
            'html' => 'html',
            'css' => 'css',
            'scss' => 'scss',
            'md' => 'markdown',
        ];

        $parser = $parserMap[$ext] ?? 'babel'; // fallback pra babel

        // Executa Prettier com input por stdin
        $process = new Process(['npx', 'prettier', '--parser', $parser]);
        $process->setInput($code);
        $process->run();

        if (!$process->isSuccessful()) {
            \Log::error('❌ Prettier formatting failed', [
                'error' => $process->getErrorOutput(),
                'extension' => $extension,
                'parser' => $parser,
            ]);
            return $code; // fallback se falhar
        }

        return $process->getOutput();
    }

    public function chatHistory(Project $project)
    {
        $this->authorize('view', $project);

        $messages = Message::where('project_id', $project->id)
            ->orderBy('created_at')
            ->get(['role', 'content', 'created_at']);

        return response()->json(['messages' => $messages]);
    }

    public function chatSend(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate([
            'content' => 'required|string',
        ]);

        $user = Auth::user();

        // Salvar mensagem do usuário
        Message::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $request->input('content'),
        ]);

        // Histórico da conversa anterior
        $history = Message::where('project_id', $project->id)
            ->orderBy('created_at')
            ->limit(10) // ou mais
            ->get()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content
            ])
            ->toArray();

        $history[] = ['role' => 'user', 'content' => $request->input('content')];

        // Chamar a IA
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => $history,
            ]);

        $reply = $response->json('choices.0.message.content');

        // Salvar resposta da IA
        Message::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return response()->json(['reply' => $reply]);
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
