<?php

namespace App\Chat\Prompts;

class FunctionResponseFormatterPrompt
{
    /**
     * Constrói o prompt para formatar dados de API em uma resposta natural.
     *
     * @param string $api_data Os dados brutos da API (em formato JSON).
     * @param string $conversation_history O histórico da conversa.
     * @return string O prompt formatado.
     */
    public function build(string $api_data, string $conversation_history): string
    {
        return <<<PROMPT
## Contexto ##
Você é um assistente de agendamento. Você recebeu a CONVERSA entre o Cliente e o Atendente, e INFORMAÇÕES AUXILIARES de uma função interna do sistema.

## Tarefa ##
1. Use as INFORMAÇÕES AUXILIARES para formular uma resposta clara e útil para o cliente, dando continuidade à CONVERSA.

## Regras ##
1. A resposta deve ser em linguagem natural, como se fosse um atendente humano.
2. Não mencione as "informações auxiliares" ou a palavra "JSON" na sua resposta.
3. Se a lista de informações estiver vazia, informe ao cliente que não encontrou o que ele pediu e pergunte se ele gostaria de tentar de outra forma.

## Conversa ##
{$conversation_history}

## Informações Auxiliares ##
{$api_data}
PROMPT;
    }
}
