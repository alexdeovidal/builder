import { useEffect, useState } from 'react';
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
    const [savedProjects, setSavedProjects] = useState<string[]>([]);
    const [prompt, setPrompt] = useState('Quero um site de portfólio moderno com React e Tailwind');
    const [project, setProject] = useState<ProjectStructure | null>(null);
    const [selectedFile, setSelectedFile] = useState<FileNode | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        axios.get('/api/projects')
            .then(res => {
                console.log('📂 Projetos recebidos:', res.data);
                setSavedProjects(Array.isArray(res.data.projects) ? res.data.projects : []);
            })
            .catch(err => console.error('Erro ao carregar projetos', err));
    }, []);

    const loadProject = async (name: string) => {
        try {
            const res = await axios.get(`/api/projects/${name}`);
            setProject(res.data.project);
            setSelectedFile(null);
        } catch (error) {
            console.error('Erro ao carregar projeto:', error);
        }
    };

    const generateProject = async () => {
        setLoading(true);
        setProject(null);
        setSelectedFile(null);
        try {
            const response = await axios.post('/api/builder', { prompt });
            setProject(response.data.project);
            // Recarrega os projetos
            const res = await axios.get('/api/projects');
            setSavedProjects(res.data.projects ?? []);
        } catch (error) {
            console.error('Erro ao gerar projeto:', error);
        } finally {
            setLoading(false);
        }
    };

    const deployToGitHub = async (name: string) => {
        try {
            const res = await axios.post(`/api/projects/${name}/deploy`);
            alert(`Projeto enviado para o GitHub com sucesso!\n${res.data.repo_url}`);
        } catch (err) {
            console.error('Erro ao enviar para o GitHub', err);
            alert('Erro ao enviar para o GitHub. Veja o console.');
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
                        savedProjects={savedProjects}
                        onSelectProject={loadProject}
                        onDeploy={deployToGitHub} // <- ✅ novo
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
