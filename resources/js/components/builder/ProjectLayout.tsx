import FileExplorer from './FileExplorer';
import CodeEditor from './CodeEditor';

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
