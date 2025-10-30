<?php

namespace App\Chat\Applications;

use App\Chat\Contracts\ChatApplicationInterface;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Log;

class GreetingApplication implements ChatApplicationInterface
{
    /**
     * O orquestrador determina se esta aplicação deve ser executada.
     */
    public function shouldHandle(string $message, ChatSession $session): bool
    {
        // Esta verificação agora é centralizada e baseada na intenção.
        return false;
    }

    /**
     * Retorna a mensagem de saudação.
     * Esta é uma ação "sem estado" e não deve modificar o estado da sessão.
     */
    public function handle(string $message, ChatSession $session): string
    {
        Log::info('[GreetingApplication] Manipulando a mensagem de saudação.');

        // A aplicação de saudação não define um 'flow', permitindo que a
        // próxima mensagem do usuário seja analisada do zero pelo orquestrador.
        return "Olá! Sou o assistente de agendamento da Pés Sem Dor. Gostaria de agendar uma avaliação?";
    }
}
