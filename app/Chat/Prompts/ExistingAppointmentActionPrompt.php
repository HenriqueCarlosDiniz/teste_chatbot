<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA classificar a ação do utilizador sobre um agendamento existente.
 */
class ExistingAppointmentActionPrompt
{
    public function build(string $user_message): string
    {
        return <<<PROMPT
Você é um assistente especialista em agendamentos de Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Identifique palavras-chave na mensagem. 2. Compare com ações possíveis: confirmar, cancelar ou reagendar. 3. Avalie a confiança na classificação (0.0 a 1.0).

A intenção dele é 'confirmar', 'cancelar' ou 'reagendar'?
Responda APENAS com um objeto JSON: {"action": "confirmar/cancelar/reagendar", "confidence": número}.

Exemplos:
- Mensagem: "quero confirmar" -> {"action": "confirmar", "confidence": 1.0}
- Mensagem: "sim, confirmo" -> {"action": "confirmar", "confidence": 1.0}
- Mensagem: "pode cancelar" -> {"action": "cancelar", "confidence": 1.0}
- Mensagem: "não quero mais" -> {"action": "cancelar", "confidence": 0.9}
- Mensagem: "gostaria de mudar a data" -> {"action": "reagendar", "confidence": 1.0}
- Mensagem: "troca pra outro dia" -> {"action": "reagendar", "confidence": 0.95}
- Mensagem: "tudo bem, vai nessa" -> {"action": "confirmar", "confidence": 0.8}
- Mensagem: "desisto, cancela aí" -> {"action": "cancelar", "confidence": 0.9}

Mensagem do usuário: "{$user_message}"
PROMPT;
    }
}
