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

Pense passo a passo:
1. Identifique menções a estados (UFs do Brasil) ou CEPs (8 dígitos).
2. Se encontrar um estado, normalize para a sigla (ex: "São Paulo" -> "SP").
3. Se encontrar um CEP, normalize para 8 números (ex: "01001-000" -> "01001000").
4. Se encontrar uma cidade (ex: "Campinas") ou bairro (ex: "Pinheiros"), classifique como "unknown", pois o sistema só aceita estados ou CEPs nesta etapa.
5. Se não encontrar nada, classifique como "unknown".

Analise a mensagem do usuário e extraia uma sigla de estado brasileiro (UF com 2 letras) ou um CEP (8 dígitos).

Responda APENAS com um objeto JSON: {"type": "state/cep/unknown", "value": string/null}.

Exemplos:
- Mensagem: "sou de minas gerais" -> {"type": "state", "value": "MG"}
- Mensagem: "meu cep é 01001-000" -> {"type": "cep", "value": "01001000"}
- Mensagem: "Estou em São Paulo" -> {"type": "state", "value": "SP"}
- Mensagem: "Estou em São Paulo Sp" -> {"type": "state", "value": "SP"}
- Mensagem: "quero agendar" -> {"type": "unknown", "value": null}
- Mensagem: "tô no Rio" -> {"type": "state", "value": "RJ"}
- Mensagem: "em Pinheiros" -> {"type": "unknown", "value": "Pinheiros"}
- Mensagem: "Campinas" -> {"type": "unknown", "value": "Campinas"}
- Mensagem: "Não sabe onde fica São Paulo?" -> {"type": "state", "value": "SP"}

Mensagem do Usuário:
"{$user_message}"
PROMPT;
    }
}