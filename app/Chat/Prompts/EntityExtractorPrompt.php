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
    public function build(string $user_message, string $history = ''): string
    {
        $today = now()->format('d/m/Y');

        return <<<PROMPT
## Contexto ##
Você é um especialista em extração de informações (entidades) de texto. A data de hoje é {$today}.

## Tarefa ##
1. Analise a MENSAGEM DO CLIENTE e o HISTÓRICO para extrair as seguintes entidades: NOME, TELEFONE, DATA, HORA e NOME_UNIDADE.
2. Se uma entidade não for encontrada, retorne `null` para seu valor.

## Regras ##
1. Normalize as informações:
   - TELEFONE: Apenas números.
   - DATA: Formato AAAA-MM-DD.
   - HORA: Formato HH:MM (24h).
   - NOME_UNIDADE: O nome da unidade (ex: "Tatuapé", "Pinheiros").
2. A resposta deve ser APENAS um objeto JSON.

## Saída ##
{"name": "string|null", "phone": "string|null", "date": "string|null", "time": "string|null", "unit_name": "string|null"}

## Histórico ##
{$history}

MENSAGEM DO CLIENTE: "{$user_message}"
PROMPT;
    }
}