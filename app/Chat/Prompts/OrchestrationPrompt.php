<?php

namespace App\Chat\Prompts;

class OrchestrationPrompt
{
    // ALTERADO: Agora recebe histórico como parâmetro opcional
    public function build(string $user_message, string $history = ''): string
    {
        $possible_intents = implode("', '", ['agendamento', 'consultar_agendamento', 'cancelamento', 'informacao_geral', 'saudacao', 'afirmativa', 'negativa']);

        return <<<PROMPT
Você é um assistente especialista em agendamentos da Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Revise o histórico da conversa para contexto. 2. Classifique intenção. 3. Verifique se é pergunta. 4. Extraia localização (incluindo zona). 5. Avalie confiança.

Histórico da conversa (considere para contexto):
{$history}

Retorne JSON: {"intent": "string", "is_question": boolean, "location": {"type": "string", "value": "string"}|null, "confidence": número}.

Opções de intent: '{$possible_intents}' ou 'desconhecida'.

Exemplos:
- Histórico: Bot: Gostaria de agendar uma avaliação gratuita? Mensagem: "Gostaria" -> {"intent": "afirmativa", "is_question": false, "location": null, "confidence": 1.0}
- Mensagem: "quais as opções no rio de janeiro?" -> {"intent": "informacao_geral", "is_question": true, "location": {"type": "state", "value": "RJ"}, "confidence": 1.0}
- Mensagem: "Gostaria de agendar em SP" -> {"intent": "agendamento", "is_question": false, "location": {"type": "state", "value": "SP"}, "confidence": 1.0}
- Mensagem: "tem em Campinas?" -> {"intent": "informacao_geral", "is_question": true, "location": {"type": "city", "value": "Campinas"}, "confidence": 1.0}
- Mensagem: "e no bairro de Moema?" -> {"intent": "informacao_geral", "is_question": true, "location": {"type": "neighborhood", "value": "Moema"}, "confidence": 1.0}
- Mensagem: "meu cep é 01001-000" -> {"intent": "informacao_geral", "is_question": false, "location": {"type": "cep", "value": "01001000"}, "confidence": 1.0}
- Mensagem: "quero agendar" -> {"intent": "agendamento", "is_question": false, "location": null, "confidence": 1.0}
- Mensagem: "na zona sul de SP" -> {"intent": "informacao_geral", "is_question": true, "location": {"type": "zona", "value": "zona sul"}, "confidence": 0.95}
- Mensagem: "sim, confirma" -> {"intent": "afirmativa", "is_question": false, "location": null, "confidence": 0.9}

Mensagem atual: "{$user_message}"
PROMPT;
    }
}
