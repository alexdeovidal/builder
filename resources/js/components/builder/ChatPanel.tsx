import { useEffect, useState } from 'react';
import axios from 'axios';

interface Message {
  role: string;
  content: string;
}

export default function ChatPanel({ projectName }: { projectName: string }) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState('');

  const fetchMessages = async () => {
    const res = await axios.get(`/api/projects/${projectName}/messages`);
    setMessages(res.data.messages);
  };

  const sendMessage = async () => {
    if (!input.trim()) return;
    await axios.post(`/api/projects/${projectName}/chat`, { content: input });
    setInput('');
    fetchMessages();
  };

  useEffect(() => {
    fetchMessages();
  }, []);

  return (
    <div className="bg-neutral-900 rounded-xl p-4 text-sm">
      <div className="space-y-2 max-h-96 overflow-auto">
        {messages.map((m, i) => (
          <div key={i} className={`p-2 rounded ${m.role === 'user' ? 'bg-blue-700' : 'bg-green-700'}`}>
            <strong>{m.role}:</strong> {m.content}
          </div>
        ))}
      </div>

      <div className="mt-4 flex gap-2">
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          className="flex-1 rounded bg-neutral-800 p-2"
          placeholder="Diga à IA o que deseja adicionar..."
        />
        <button onClick={sendMessage} className="bg-blue-600 px-4 py-2 rounded">
          Enviar
        </button>
      </div>
    </div>
  );
}
