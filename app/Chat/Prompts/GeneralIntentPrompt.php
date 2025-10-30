<?php

namespace App\Chat\Prompts;

class GeneralIntentPrompt
{
    public function build(string $user_message): string
    {
        $possible_intents = ['agendamento', 'consultar_agendamento', 'cancelamento', 'informacao_geral', 'saudacao'];
        $formatted_intents = implode("', '", $possible_intents);

        return <<<PROMPT
Você é um assistente especialista em agendamentos da Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Identifique intenção principal. 2. Considere contexto de conversa anterior se aplicável. 3. Avalie confiança.

Seu objetivo é ajudar com: agendamento, consultar_agendamento, cancelamento, informacao_geral ou saudacao.

Classifique em uma das categorias: '{$formatted_intents}'.

Responda APENAS com JSON: {"intent": "string", "confidence": número}.

Exemplos:
- Mensagem: "Quero marcar" -> {"intent": "agendamento", "confidence": 1.0}
- Mensagem: "Verificar meu horário" -> {"intent": "consultar_agendamento", "confidence": 1.0}
- Mensagem: "Não poderei ir" -> {"intent": "cancelamento", "confidence": 0.9}
- Mensagem: "Qual o endereço da unidade de Pinheiros?" -> {"intent": "informacao_geral", "confidence": 1.0}
- Mensagem: "oi, tudo bem?" -> {"intent": "saudacao", "confidence": 1.0}
- Mensagem: "sim, quero marcar em BH" -> {"intent": "agendamento", "confidence": 0.95}
- Mensagem: "troca o dia?" -> {"intent": "cancelamento", "confidence": 0.8}
- Mensagem: "e sobre palmilhas?" -> {"intent": "informacao_geral", "confidence": 0.85}

Mensagem do usuário: "{$user_message}"
PROMPT;
    }
}