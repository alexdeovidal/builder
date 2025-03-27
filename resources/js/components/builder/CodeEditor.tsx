import Editor from '@monaco-editor/react';

interface FileNode {
    name: string;
    type: 'file' | 'folder';
    content?: string;
}

interface CodeEditorProps {
    file: FileNode;
    onUpdate: (content: string) => void;
}

export default function CodeEditor({ file, onUpdate }: CodeEditorProps) {
    return (
        <div>
            <h3 className="text-blue-400 font-mono text-sm mb-2">{file.name}</h3>
            <Editor
                height="60vh"
                defaultLanguage="javascript"
                theme="vs-dark"
                value={file.content}
                onChange={(value) => value && onUpdate(value)}
                options={{
                    fontSize: 14,
                    minimap: { enabled: false },
                    scrollBeyondLastLine: false,
                    wordWrap: 'on',
                }}
            />
        </div>
    );
}
