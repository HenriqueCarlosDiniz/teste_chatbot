<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA identificar se a intenção do usuário é gerenciar um agendamento.
 */
class ExtractAppointmentManagementIntentPrompt
{
    public function build(string $user_message): string
    {
        return <<<PROMPT
Analise a mensagem do usuário para identificar se a intenção principal é 'confirmar', 'cancelar' ou 'reagendar' um agendamento.
Se a intenção não for clara ou não estiver relacionada a uma dessas três ações, responda 'nenhuma'.
Responda APENAS com uma das quatro palavras: 'confirmar', 'cancelar', 'reagendar', ou 'nenhuma'.

Exemplos:
- Mensagem: "eu preciso cancelar minha consulta" -> cancelar
- Mensagem: "quero confirmar meu horário" -> confirmar
- Mensagem: "preciso mudar o dia" -> reagendar
- Mensagem: "quero agendar uma avaliação" -> nenhuma
- Mensagem: "oi, tudo bem?" -> nenhuma

Mensagem do usuário: "{$user_message}"
PROMPT;
    }
}
