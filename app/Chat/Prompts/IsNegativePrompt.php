<?php

namespace App\Chat\Prompts;

class IsNegativePrompt
{
    /**
     * Constrói o prompt para verificar se uma mensagem é uma negação.
     *
     * @param string $message A mensagem do utilizador.
     * @return string
     */
    public function build(string $message): string
    {
        return <<<PROMPT
A mensagem do utilizador é uma negação, discordância ou resposta negativa?
Responda APENAS com a palavra 'sim' ou 'não'.

Exemplos:
- Mensagem: "não, obrigado" -> sim
- Mensagem: "deixa pra lá" -> sim
- Mensagem: "não quero" -> sim
- Mensagem: "pode ser" -> não
- Mensagem: "sim, por favor" -> não

Mensagem do utilizador: "{$message}"
PROMPT;
    }
}
