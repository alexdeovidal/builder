import { useState } from 'react';
import axios from 'axios';

export default function Chat() {
    const [message, setMessage] = useState('');
    const [reply, setReply] = useState('');
    const [loading, setLoading] = useState(false);

    const sendMessage = async () => {
        if (!message.trim()) return;

        setLoading(true);
        try {
            const response = await axios.post('/api/chat', {
                message: message,
            });

            setReply(response.data.reply);
        } catch (error) {
            console.error(error);
            setReply('Erro ao buscar resposta.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="p-6">
            <h1 className="text-xl font-bold mb-4">Chat com OpenAI</h1>
            <textarea
                rows="4"
                className="w-full p-2 border rounded mb-4"
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                placeholder="Digite uma pergunta..."
            />
            <button
                className="bg-blue-500 text-white px-4 py-2 rounded"
                onClick={sendMessage}
                disabled={loading}
            >
                {loading ? 'Enviando...' : 'Enviar'}
            </button>

            <div className="mt-4">
                <h2 className="font-semibold">Resposta:</h2>
                <p>{reply}</p>
            </div>
        </div>
    );
}
