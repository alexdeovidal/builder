import { useState } from 'react';
import axios from 'axios';

import Header from '@/components/builder/Header';
import PromptForm from '@/components/builder/PromptForm';
import ProjectLayout from '@/components/builder/ProjectLayout';

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

    const updateSelectedContent = (content: string) => {
        if (!selectedFile) return;
        setSelectedFile({ ...selectedFile, content });
    };

    return (
        <div className="min-h-screen bg-neutral-950 text-white">
            <Header />
            <div className="max-w-7xl mx-auto px-6 py-8">
                {!project ? (
                    <PromptForm
                        prompt={prompt}
                        setPrompt={setPrompt}
                        onGenerate={generateProject}
                        loading={loading}
                    />
                ) : (
                    <ProjectLayout
                        project={project}
                        selectedFile={selectedFile}
                        setSelectedFile={setSelectedFile}
                        updateSelectedContent={updateSelectedContent}
                    />
                )}
            </div>
        </div>
    );
}
