import { useState } from 'react';
import axios from 'axios';
import Editor from '@monaco-editor/react';

interface FileNode {
    name: string;
    type: 'file' | 'folder';
    content?: string;
    children?: FileNode[];
}

interface ProjectStructure {
    project_name: string;
    structure: FileNode[];
}

export default function BuilderPage() {
    const [prompt, setPrompt] = useState('Quero um site de portfólio moderno com React e Tailwind');
    const [project, setProject] = useState<ProjectStructure | null>(null);
    const [selectedFile, setSelectedFile] = useState<FileNode | null>(null);
    const [loading, setLoading] = useState(false);

    const generateProject = async () => {
        setLoading(true);
        setProject(null);
        setSelectedFile(null);
        try {
            const response = await axios.post('/api/builder', { prompt });
            setProject(response.data.project);
        } catch (error) {
            console.error('Erro ao gerar projeto:', error);
        } finally {
            setLoading(false);
        }
    };

    const renderStructure = (nodes: FileNode[]) => {
        return nodes.map((node) => (
            <div key={node.name} className="ml-2">
                {node.type === 'folder' ? (
                    <div className="mt-1">
                        <strong className="text-yellow-400">📁 {node.name}</strong>
                        <div className="ml-4 border-l border-neutral-700 pl-2">
                            {node.children && renderStructure(node.children)}
                        </div>
                    </div>
                ) : (
                    <button
                        onClick={() => setSelectedFile(node)}
                        className="text-left w-full mt-1 text-blue-400 hover:text-blue-300 font-mono text-sm"
                    >
                        📄 {node.name}
                    </button>
                )}
            </div>
        ));
    };

    return (
        <div className="min-h-screen bg-neutral-950 text-white">
            <div className="px-6 py-8 border-b border-neutral-800">
                <div className="max-w-7xl mx-auto">
                    <h1 className="text-3xl font-bold">Builder IA</h1>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-6 py-8">
                {!project ? (
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
                            onClick={generateProject}
                            className="bg-blue-600 hover:bg-blue-500 transition px-6 py-3 rounded-xl"
                            disabled={loading}
                        >
                            {loading ? 'Gerando...' : 'Criar Projeto'}
                        </button>
                    </div>
                ) : (
                    <div className="grid grid-cols-12 gap-4">
                        <aside className="col-span-3 bg-neutral-900 rounded-xl p-4 overflow-y-auto max-h-[70vh]">
                            <h2 className="text-lg font-semibold mb-2">📁 {project.project_name}</h2>
                            {renderStructure(project.structure)}
                        </aside>

                        <main className="col-span-9 bg-neutral-900 rounded-xl p-4 min-h-[70vh] overflow-auto">
                            {selectedFile ? (
                                <div>
                                    <h3 className="text-blue-400 font-mono text-sm mb-2">{selectedFile.name}</h3>
                                    <Editor
                                        height="60vh"
                                        defaultLanguage="javascript"
                                        theme="vs-dark"
                                        value={selectedFile.content}
                                        options={{ readOnly: true }}
                                    />
                                </div>
                            ) : (
                                <p className="text-neutral-400">Selecione um arquivo para visualizar o conteúdo.</p>
                            )}
                        </main>
                    </div>
                )}
            </div>
        </div>
    );
}
