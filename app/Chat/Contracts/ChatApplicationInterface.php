<?php

namespace App\Chat\Contracts;

use App\Models\ChatSession;

/**
 * Define o contrato para as aplicações de chat que podem manipular
 * diferentes partes de uma conversa.
 */
interface ChatApplicationInterface
{
    /**
     * Determina se esta aplicação deve manipular a mensagem atual do usuário.
     *
     * @param string $message A mensagem enviada pelo usuário.
     * @param ChatSession $session O objeto da sessão de chat, contendo o histórico e o contexto.
     * @return bool Retorna true se a aplicação deve manipular a mensagem, false caso contrário.
     */
    public function shouldHandle(string $message, ChatSession $session): bool;

    /**
     * Processa a mensagem do usuário e retorna a resposta do chatbot.
     *
     * @param string $message A mensagem enviada pelo usuário.
     * @param ChatSession $session O objeto da sessão de chat.
     * @return string A resposta do bot para ser enviada ao usuário.
     */
    public function handle(string $message, ChatSession $session): string;
}
