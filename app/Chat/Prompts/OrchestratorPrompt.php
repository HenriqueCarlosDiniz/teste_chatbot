<?php

namespace App\Chat\Prompts;

class OrchestratorPrompt
{
    /**
     * Constrói o prompt para o orquestrador principal da IA.
     *
     * @param string $user_message A mensagem atual do usuário.
     * @param string $history O histórico formatado da conversa.
     * @return string O prompt formatado.
     */
    public function build(string $user_message, string $history = ''): string
    {
        $possible_intents = implode("', '", [
            'agendamento',
            'consultar_agendamento',
            'cancelamento',
            'informacao_geral',
            'saudacao',
            'afirmativa',
            'negativa',
            'desconhecida'
        ]);

        return <<<PROMPT
## Contexto ##
Você é um Orquestrador de um chatbot de agendamento. Sua tarefa é analisar a MENSAGEM DO CLIENTE e o HISTÓRICO da conversa para classificar a intenção principal.

## Tarefa ##
1. Pense passo a passo para determinar a intenção mais provável.
2. Analise o HISTÓRICO para entender o contexto.
3. Classifique a MENSAGEM DO CLIENTE em uma das categorias da LISTA.
4. Atribua um score de confiança (confidence) de 0.0 a 1.0.

## Saída ##
Forneça a resposta APENAS em formato JSON: {"intent": "categoria", "confidence": 1.0}

## Lista de Intenções ##
'{$possible_intents}'

## Histórico ##
{$history}

MENSAGEM DO CLIENTE: "{$user_message}"
PROMPT;
    }
}
