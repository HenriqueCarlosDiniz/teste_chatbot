<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA extrair critérios de filtro da mensagem do utilizador.
 */
class ExtractFilterCriteriaPrompt
{
    public function build(string $userMessage): string
    {
        return <<<PROMPT
Você é um assistente especialista em agendamentos d Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Identifique critérios como cidade, bairro ou zona. 2. Normalize (ex: "capital" para "são paulo"). 3. Avalie confiança.

Extraia critérios de filtro. Responda APENAS com JSON: {"cidade": string/null, "bairro": string/null, "zona": string/null, "confidence": número}.

Exemplos:
- Mensagem: "quais as opções na capital?" -> {"cidade": "são paulo", "bairro": null, "zona": null, "confidence": 1.0}
- Mensagem: "tem alguma no bairro de moema?" -> {"cidade": null, "bairro": "moema", "zona": null, "confidence": 1.0}
- Mensagem: "unidades da zona leste" -> {"cidade": null, "bairro": null, "zona": "zona leste", "confidence": 1.0}
- Mensagem: "quero ver todas" -> {"cidade": null, "bairro": null, "zona": null, "confidence": 1.0}
- Mensagem: "em Belo Horizonte" -> {"cidade": "belo horizonte", "bairro": null, "zona": null, "confidence": 0.95}
- Mensagem: "centro de SP" -> {"cidade": "são paulo", "bairro": null, "zona": "centro", "confidence": 0.9}
- Mensagem: "perto do metrô" -> {"cidade": null, "bairro": null, "zona": null, "confidence": 0.8}

Mensagem do usuário: "{$userMessage}"
PROMPT;
    }
}
