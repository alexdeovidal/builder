
interface SavedProject {
    name: string;
    repo_url?: string;
}
interface PromptFormProps {
    prompt: string;
    setPrompt: (value: string) => void;
    onGenerate: () => void;
    loading: boolean;
    savedProjects: SavedProject[];
    onSelectProject: (name: string) => void;
    onDeploy: (name: string) => void;
}

export default function PromptForm({
                                       prompt,
                                       setPrompt,
                                       onGenerate,
                                       loading,
                                       savedProjects,
                                       onSelectProject,
                                       onDeploy,
                                   }: PromptFormProps) {
    return (
        <div className="space-y-6 max-w-2xl">
            <p className="text-neutral-400">Descreva o projeto que você quer e deixe a IA criar tudo pra você.</p>

            {/* LISTA DE PROJETOS */}
            {savedProjects.map((project) => (
                <li key={project.name} className="flex items-center justify-between">
                    <button
                        onClick={() => onSelectProject(project.name)}
                        className="text-blue-400 hover:underline text-sm"
                    >
                        📂 {project.name}
                    </button>

                    {!project.repo_url ? (
                        <button
                            onClick={() => onDeploy(project.name)}
                            className="text-green-400 text-xs hover:underline ml-2"
                        >
                            Enviar para o GitHub
                        </button>
                    ) : (
                        <a
                            href={`https://github.dev/alexdeovidal/${project.name}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-yellow-400 text-xs hover:underline ml-2"
                        >
                            Editar no GitHub
                        </a>
                    )}
                </li>
            ))}


            <textarea
                className="w-full p-4 bg-neutral-900 border border-neutral-700 rounded-xl"
                rows={4}
                value={prompt}
                onChange={(e) => setPrompt(e.target.value)}
                placeholder="Ex: Crie um site de receitas com busca e categorias"
            />

            <button
                onClick={onGenerate}
                className="bg-blue-600 hover:bg-blue-500 transition px-6 py-3 rounded-xl"
                disabled={loading}
            >
                {loading ? 'Gerando...' : 'Criar Projeto'}
            </button>
        </div>
    );
}
