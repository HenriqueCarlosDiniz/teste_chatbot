<?php

namespace App\Chat\Prompts;

class IsAffirmativePrompt
{
    /**
     * Constrói o prompt para verificar se uma mensagem é afirmativa.
     *
     * @param string $message A mensagem do utilizador.
     * @return string
     */
    public function build(string $message): string
    {
        return <<<PROMPT
A mensagem do utilizador é uma afirmação, concordância ou resposta positiva?
Responda APENAS com a palavra 'sim' ou 'não'.

Exemplos:
- Mensagem: "sim, pode ser" -> sim
- Mensagem: "quero sim" -> sim
- Mensagem: "claro" -> sim
- Mensagem: "não, obrigado" -> não
- Mensagem: "acho que não" -> não

Mensagem do utilizador: "{$message}"
PROMPT;
    }
}
