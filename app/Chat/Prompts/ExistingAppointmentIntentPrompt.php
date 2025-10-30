<?php

namespace App\Chat\Prompts;

class ExistingAppointmentIntentPrompt
{
    public function build(string $user_message): string
    {
        return <<<PROMPT
Analise a mensagem do usuário, que já sabe que tem um agendamento.
A intenção dele é 'confirmar', 'cancelar' ou 'reagendar'?
Responda APENAS com uma dessas três palavras.

Mensagem: "{$user_message}"
PROMPT;
    }
}
