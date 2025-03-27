interface FileNode {
    name: string;
    type: 'file' | 'folder';
    content?: string;
    children?: FileNode[];
}

interface FileExplorerProps {
    structure: FileNode[];
    onSelect: (file: FileNode) => void;
}

export default function FileExplorer({ structure, onSelect }: FileExplorerProps) {
    const render = (nodes: FileNode[]) => nodes.map((node) => (
        <div key={node.name} className="ml-2">
            {node.type === 'folder' ? (
                <div className="mt-1">
                    <strong className="text-yellow-400">📁 {node.name}</strong>
                    <div className="ml-4 border-l border-neutral-700 pl-2">
                        {node.children && render(node.children)}
                    </div>
                </div>
            ) : (
                <button
                    onClick={() => onSelect(node)}
                    className="text-left w-full mt-1 text-blue-400 hover:text-blue-300 font-mono text-sm"
                >
                    📄 {node.name}
                </button>
            )}
        </div>
    ));

    return <div>{render(structure)}</div>;
}
