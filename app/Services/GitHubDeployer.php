<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class GitHubDeployer
{
    public function deploy(string $projectName, string $path)
    {
        $repoName = Str::slug($projectName);
        $githubUser = config('services.github.username');

        // 1. Cria o repositório no GitHub
        $response = Http::withToken(config('services.github.token'))
            ->post('https://api.github.com/user/repos', [
                'name' => $repoName,
                'private' => false,
                'auto_init' => false,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao criar repositório no GitHub');
        }

        $repoUrl = "https://{$githubUser}:" . config('services.github.token') . "@github.com/{$githubUser}/{$repoName}.git";

        // 2. Inicializa repositório localmente e faz push
        Process::path($path)->run(['git', 'init']);
        Process::path($path)->run(['git', 'add', '.']);
        Process::path($path)->run(['git', 'commit', '-m', 'Projeto gerado pela IA']);
        Process::path($path)->run(['git', 'branch', '-M', 'main']);
        Process::path($path)->run(['git', 'remote', 'add', 'origin', $repoUrl]);
        Process::path($path)->run(['git', 'push', '-u', 'origin', 'main']);

        return $response->json('html_url');
    }
}
