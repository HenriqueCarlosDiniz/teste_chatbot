<?php

namespace App\Chat\Prompts;

class EntityExtractorPrompt
{
    /**
     * Constrói o prompt para extrair entidades da mensagem do usuário.
     *
     * @param string $user_message A mensagem do usuário.
     * @return string O prompt formatado.
     */
    public function build(string $user_message): string
    {
        $today = now()->format('d/m/Y');

        return <<<PROMPT
## Contexto ##
Você é um especialista em extração de informações (entidades) de texto. A data de hoje é {$today}.

## Tarefa ##
1. Analise a MENSAGEM DO CLIENTE e extraia as seguintes entidades: NOME, TELEFONE, DATA e HORA.
2. Se uma entidade não for encontrada, retorne `null` para seu valor.

## Regras ##
1. Normalize as informações:
   - TELEFONE: Apenas números.
   - DATA: Formato AAAA-MM-DD.
   - HORA: Formato HH:MM (24h).
2. A resposta deve ser APENAS um objeto JSON.

## Saída ##
{"NOME": "string|null", "TELEFONE": "string|null", "DATA": "string|null", "HORA": "string|null"}

MENSAGEM DO CLIENTE: "{$user_message}"
PROMPT;
    }
}
