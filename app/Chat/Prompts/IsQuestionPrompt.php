<?php

namespace App\Chat\Prompts;

class IsQuestionPrompt
{
    /**
     * Constrói o prompt para verificar se uma mensagem é uma pergunta.
     *
     * @param string $message A mensagem do utilizador.
     * @return string
     */
    public function build(string $message): string
    {
        return <<<PROMPT
A mensagem do utilizador é uma pergunta?
Responda APENAS com a palavra 'sim' ou 'não'.

Exemplos:
- Mensagem: "quanto custa?" -> sim
- Mensagem: "tem horário amanhã?" -> sim
- Mensagem: "e se for na sexta?" -> sim
- Mensagem: "quero agendar" -> não
- Mensagem: "pode ser às 15h" -> não

Mensagem do utilizador: "{$message}"
PROMPT;
    }
}
