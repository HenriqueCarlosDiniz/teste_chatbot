<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA extrair uma localização da mensagem do usuário.
 */
class ExtractLocationPrompt
{
    public function build(string $user_message): string
    {
        return <<<PROMPT
Você é um assistente especialista em agendamentos de Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Identifique menções a estados, cidades, bairros ou CEP. 2. Normalize valores (ex: "Sao Paulo" para "SP"). 3. Avalie confiança.

Analise a mensagem do usuário e extraia uma sigla de estado brasileiro (UF com 2 letras), um CEP (8 dígitos), cidade ou bairro.

Responda APENAS com um objeto JSON: {"type": "state/cep/city/neighborhood/unknown", "value": string/null, "confidence": número}.

Exemplos:
- Mensagem: "sou de minas gerais" -> {"type": "state", "value": "MG", "confidence": 1.0}
- Mensagem: "meu cep é 01001-000" -> {"type": "cep", "value": "01001000", "confidence": 1.0}
- Mensagem: "Estou em São Paulo" -> {"type": "state", "value": "SP", "confidence": 1.0}
- Mensagem: "quero agendar" -> {"type": "unknown", "value": null, "confidence": 1.0}
- Mensagem: "tô no Rio" -> {"type": "state", "value": "RJ", "confidence": 0.95}
- Mensagem: "em Pinheiros" -> {"type": "neighborhood", "value": "Pinheiros", "confidence": 1.0}
- Mensagem: "Campinas é boa?" -> {"type": "city", "value": "Campinas", "confidence": 0.9}
- Mensagem: "zona sul de Sampa" -> {"type": "neighborhood", "value": "zona sul", "confidence": 0.85}

Mensagem do usuário: "{$user_message}"
PROMPT;
    }
}
