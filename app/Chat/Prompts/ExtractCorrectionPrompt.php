<?php

namespace App\Chat\Prompts;

/**
 * Constrói o prompt para a IA identificar qual parte do agendamento o usuário deseja corrigir.
 */
class ExtractCorrectionPrompt
{
    public function build(string $user_message): string
    {
        return <<<PROMPT
Analise a mensagem do usuário, que está a indicar qual parte de um resumo de agendamento está incorreta.
As opções possíveis são: 'unidade', 'data', 'nome', 'telefone'.

Identifique qual dessas quatro opções o usuário quer corrigir.
Responda APENAS com uma única palavra: 'unidade', 'data', 'nome', ou 'telefone'.
Se não tiver certeza, responda 'desconhecido'.

Exemplos:
- Mensagem: "a data está errada" -> data
- Mensagem: "na verdade é em outra unidade" -> unidade
- Mensagem: "meu nome não é esse" -> nome
- Mensagem: "o telefone que passei está incorreto" -> telefone
- Mensagem: "o horário" -> data

Mensagem do usuário: "{$user_message}"
PROMPT;
    }
}
