<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA identificar qual unidade o usuário escolheu de uma lista.
 */
class UnitChoicePrompt
{
    public function build(string $user_message, array $units): string
    {
        $unit_list_for_prompt = "";
        foreach ($units as $index => $unit) {
            $unit_list_for_prompt .= ($index + 1) . ". " . $unit['nomeFranquia'] . " (" . $unit['bairroFranquia'] . ", " . $unit['cidadeFranquia'] . ")\n";
        }

        return <<<PROMPT
Você é um assistente especialista em agendamentos da Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Analise referências a nomes, bairros ou números. 2. Compare com a lista. 3. Avalie confiança; se ambíguo, retorne null.

Lista de Unidades Apresentadas:
{$unit_list_for_prompt}

Mensagem do usuário: "{$user_message}"

Responda APENAS com JSON: {"choice": objeto/null, "confidence": número}.

Exemplos:
- Mensagem: "Unidade Santana" -> {"choice": { ... }, "confidence": 1.0}
- Mensagem: "a número 9" -> {"choice": { ... }, "confidence": 1.0}
- Mensagem: "e no Ipiranga?" -> {"choice": null, "confidence": 0.9}
- Mensagem: "qual o endereço da primeira?" -> {"choice": null, "confidence": 1.0}
- Mensagem: "a de Moema" -> {"choice": { ... }, "confidence": 0.95}
- Mensagem: "zona sul, a segunda" -> {"choice": { ... }, "confidence": 0.85}
- Mensagem: "não gostei, outra" -> {"choice": null, "confidence": 0.8}

PROMPT;
    }
}
