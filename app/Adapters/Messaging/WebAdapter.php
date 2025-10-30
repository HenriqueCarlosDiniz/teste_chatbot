<?php

namespace App\Adapters\Messaging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Class WebAdapter
 *
 * Adaptador para interação via interface web local.
 * Utiliza a sessão do Laravel para armazenar o histórico da conversa.
 */
class WebAdapter implements MessagingAdapterInterface
{
    /**
     * Processa a requisição vinda do formulário web.
     */
    public function processIncoming(Request $request): array
    {
        // Para a web, o senderId pode ser o ID da sessão para simular um usuário único.
        $senderId = Session::getId();
        $message = $request->input('message');

        // Adiciona a mensagem do usuário ao histórico da sessão.
        Session::push('chat_history', ['sender' => 'user', 'message' => $message]);

        return [
            'senderId' => $senderId,
            'message' => $message,
        ];
    }

    /**
     * "Envia" a resposta adicionando-a ao histórico da sessão.
     */
    public function sendResponse(string $recipientId, string $message): bool
    {
        // Adiciona a resposta do bot ao histórico da sessão.
        Session::push('chat_history', ['sender' => 'bot', 'message' => $message]);
        return true;
    }
}
