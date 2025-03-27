import axios from 'axios';
import CodeEditor from './CodeEditor';
import FileExplorer from './FileExplorer';

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
        <div className="grid">
            <div className="mt-4 flex gap-4">
                <button
                    className="rounded bg-green-600 px-4 py-2 text-sm hover:bg-green-500"
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
                    className="rounded bg-indigo-600 px-4 py-2 text-sm hover:bg-indigo-500"
                    href={`/api/projects/${project.project_name}/zip`}
                    target="_blank"
                >
                    📦 Baixar ZIP
                </a>
            </div>

            <div className="grid grid-cols-12 gap-4">
                <aside className="col-span-3 max-h-[70vh] overflow-y-auto rounded-xl bg-neutral-900 p-4">
                    <h2 className="mb-2 text-lg font-semibold">📁 {project.project_name}</h2>
                    <FileExplorer structure={project.structure} onSelect={setSelectedFile} />
                </aside>

                <main className="col-span-9 min-h-[70vh] overflow-auto rounded-xl bg-neutral-900 p-4">
                    {selectedFile ? (
                        <CodeEditor file={selectedFile} onUpdate={updateSelectedContent} />
                    ) : (
                        <p className="text-neutral-400">Selecione um arquivo para visualizar o conteúdo.</p>
                    )}
                </main>
            </div>
        </div>
    );
}
