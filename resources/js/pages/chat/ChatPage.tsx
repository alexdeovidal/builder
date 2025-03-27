import { useState } from 'react';
import axios from 'axios';

export default function ChatPage() {
    const [message, setMessage] = useState('');
    const [reply, setReply] = useState('');
    const [loading, setLoading] = useState(false);

    const sendMessage = async () => {
        if (!message.trim()) return;
        setLoading(true);

        try {
            const response = await axios.post('/api/chat', { message }, { withCredentials: true });
            setReply(response.data.reply);
        } catch (err) {
            setReply('Erro ao buscar resposta.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-neutral-950 text-white font-sans p-6 flex flex-col items-center">
            <div className="max-w-2xl w-full">
                <h1 className="text-3xl font-bold mb-6 text-center">💬 Chat com OpenAI</h1>

                <div className="bg-neutral-900 p-6 rounded-2xl shadow-md space-y-4">
          <textarea
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="w-full p-4 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:ring focus:ring-blue-500"
              rows={4}
              placeholder="Digite sua pergunta para a IA..."
          />

                    <button
                        onClick={sendMessage}
                        className="bg-blue-600 hover:bg-blue-500 transition-colors px-6 py-3 rounded-xl font-semibold"
                        disabled={loading}
                    >
                        {loading ? 'Enviando...' : 'Enviar'}
                    </button>

                    <div className="mt-4 bg-neutral-800 p-4 rounded-lg min-h-[100px] whitespace-pre-wrap">
                        <strong className="block text-sm text-neutral-400 mb-2">Resposta:</strong>
                        <p>{reply}</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
