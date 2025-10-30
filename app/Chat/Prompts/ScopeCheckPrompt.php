<?php

namespace App\Chat\Prompts;

class ScopeCheckPrompt
{
    public function build(string $user_message): string
    {
        return <<<PROMPT
Você é um assistente especialista em agendamentos da Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Verifique se relacionado a agendamentos, horários, unidades, endereço, saúde dos pés ou saudação. 2. Avalie confiança e razão.

Responda APENAS com JSON: {"in_scope": "yes/no", "reason": "string", "confidence": número}.

Exemplos:
- Mensagem: "quero agendar" -> {"in_scope": "yes", "reason": "relacionado a agendamento", "confidence": 1.0}
- Mensagem: "dor no pé" -> {"in_scope": "yes", "reason": "saúde dos pés", "confidence": 0.9}
- Mensagem: "pedir pizza" -> {"in_scope": "no", "reason": "assunto aleatório", "confidence": 1.0}
- Mensagem: "oi, sobre palmilhas?" -> {"in_scope": "yes", "reason": "saudação e saúde", "confidence": 0.95}

Mensagem: "{$user_message}"
PROMPT;
    }
}
