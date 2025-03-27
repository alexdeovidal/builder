import FileExplorer from './FileExplorer';
import CodeEditor from './CodeEditor';
import axios from 'axios';

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

interface Props {
    project: ProjectStructure;
    selectedFile: FileNode | null;
    setSelectedFile: (file: FileNode) => void;
    updateSelectedContent: (content: string) => void;
}

export default function ProjectLayout({ project, selectedFile, setSelectedFile, updateSelectedContent }: Props) {
    return (
        <div className="grid grid-cols-12 gap-4">
            <aside className="col-span-3 bg-neutral-900 rounded-xl p-4 overflow-y-auto max-h-[70vh]">
                <h2 className="text-lg font-semibold mb-2">📁 {project.project_name}</h2>
                <FileExplorer structure={project.structure} onSelect={setSelectedFile} />
            </aside>

            <div className="flex gap-4 mt-4">
                <button
                    className="px-4 py-2 rounded bg-green-600 hover:bg-green-500 text-sm"
                    onClick={async () => {
                        await axios.post(`/api/projects/${project.project_name}/save`, {
                            structure: project.structure,
                        });
                        alert('Projeto salvo!');
                    }}
                >
                    💾 Salvar Projeto
                </button>

                <a
                    className="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-500 text-sm"
                    href={`/api/projects/${project.project_name}/zip`}
                    target="_blank"
                >
                    📦 Baixar ZIP
                </a>
            </div>

            <main className="col-span-9 bg-neutral-900 rounded-xl p-4 min-h-[70vh] overflow-auto">
                {selectedFile ? (
                    <CodeEditor file={selectedFile} onUpdate={updateSelectedContent} />
                ) : (
                    <p className="text-neutral-400">Selecione um arquivo para visualizar o conteúdo.</p>
                )}
            </main>
        </div>
    );
}
