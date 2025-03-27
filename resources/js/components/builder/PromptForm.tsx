interface PromptFormProps {
    prompt: string;
    loading: boolean;
    setPrompt: (value: string) => void;
    onGenerate: () => void;
}

export default function PromptForm({ prompt, setPrompt, onGenerate, loading }: PromptFormProps) {
    return (
        <div className="space-y-6 max-w-2xl">
            <p className="text-neutral-400">Descreva o projeto que você quer e deixe a IA criar tudo pra você.</p>
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
