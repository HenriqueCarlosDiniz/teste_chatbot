<?php

namespace App\Chat\Flows\BookingFlow\Contracts;

use App\Models\ChatSession;
use App\Data\ConversationAnalysisDTO;

/**
 * Interface StateHandler
 * Define o contrato para todas as classes que representam um estado no fluxo de agendamento.
 */
interface StateHandler
{
    /**
     * Manipula a mensagem do usuário para o estado atual.
     *
     * @param string $message A mensagem do usuário.
     * @param ChatSession $session A sessão de chat atual.
     * @param ConversationAnalysisDTO|null $analysis A análise da conversa.
     * @return string A resposta do chatbot.
     */
    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string;
}
