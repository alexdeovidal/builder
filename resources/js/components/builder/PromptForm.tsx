interface PromptFormProps {
    prompt: string;
    setPrompt: (value: string) => void;
    onGenerate: () => void;
    loading: boolean;
    savedProjects: string[];
    onSelectProject: (name: string) => void;
    onDeploy: (name: string) => void; // ✅ nova prop
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
            {savedProjects.length > 0 && (
                <div className="bg-neutral-900 rounded-xl p-4 mb-4">
                    <h2 className="text-lg font-semibold mb-2">📁 Projetos Salvos</h2>
                    <ul className="space-y-2">
                        {savedProjects.map((name) => (
                            <li key={name} className="flex items-center justify-between">
                                <button
                                    onClick={() => onSelectProject(name)}
                                    className="text-blue-400 hover:underline text-sm"
                                >
                                    📂 {name}
                                </button>
                                <button
                                    onClick={() => onDeploy(name)}
                                    className="text-green-400 hover:underline text-xs"
                                >
                                    Enviar para o GitHub
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

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
