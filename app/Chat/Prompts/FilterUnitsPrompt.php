<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA filtrar uma lista de unidades ou responder a perguntas específicas sobre elas.
 */
class FilterUnitsPrompt
{
    public function build(string $user_question, array $units): string
    {
        $unit_list_json = json_encode($units, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Você é um assistente especialista em agendamentos da Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Analise a mensagem para escolha ou pergunta. 2. Compare com a lista de unidades. 3. Avalie confiança; se ambíguo, explique em "answer".

A lista de unidades relevantes está abaixo, em formato JSON:
{$unit_list_json}

A mensagem do usuário é: "{$user_question}"

Sua tarefa é decidir entre: 1. ESCOLHA DE UNIDADE (retorne "choice" com objeto completo). 2. PERGUNTA SOBRE A LISTA (retorne "answer" com texto).

Responda APENAS com JSON: {"choice": objeto/null, "answer": string/null, "confidence": número}.

Exemplos:
- Mensagem: "unidade Osasco" -> {"choice": { ... }, "answer": null, "confidence": 1.0}
- Mensagem: "qual o endereço da de Osasco?" -> {"choice": null, "answer": "O endereço é Av. dos Autonomistas, 896, Sala 301.", "confidence": 1.0}
- Mensagem: "a do Tatuapé" -> {"choice": { ... }, "answer": null, "confidence": 0.95}
- Mensagem: "tem na zona leste?" -> {"choice": null, "answer": "Sim, temos unidades em Tatuapé e Vila Carrão.", "confidence": 0.9}
- Mensagem: "qual a mais perto do CEP 01000?" -> {"choice": null, "answer": "A unidade Centro é a mais próxima.", "confidence": 0.85}
- Mensagem: "não sei, explica" -> {"choice": null, "answer": "Por favor, especifique uma unidade ou pergunta.", "confidence": 0.8}

PROMPT;
    }
}
