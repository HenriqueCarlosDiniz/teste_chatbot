<?php

namespace App\Chat\Prompts;

class SentimentAnalysisPrompt
{
    /**
     * Constrói o prompt para a IA classificar o sentimento de uma mensagem.
     *
     * @param string $user_message A mensagem do cliente.
     * @return string O prompt formatado.
     */
    public function build(string $user_message): string
    {
        $candidate_labels = '["Muito insatisfeito", "Insatisfeito", "Neutro", "Satisfeito", "Muito satisfeito"]';

        return <<<PROMPT
## Contexto ##
Você é um especialista em análise de sentimento. Sua tarefa é classificar a MENSAGEM DO CLIENTE em uma das categorias fornecidas.

## Tarefas ##
1. Identifique a categoria à qual a MENSAGEM DO CLIENTE pertence com a maior probabilidade.
2. Atribua apenas 1 categoria.
3. Forneça sua resposta como um arquivo JSON contendo uma única chave "label" e o valor correspondente à categoria atribuída. Não forneça qualquer informação adicional.

## Saída ##
Arquivo JSON de saída: {"label": "categoria"}

## Lista ##
Lista de categorias: {$candidate_labels}

## Regras ##
- A MENSAGEM DO CLIENTE pertence a apenas 1 categoria.
- Classifique como 'Muito insatisfeito' se o cliente escrever expressões ofensivas.
- Classifique como 'Insatisfeito' se o cliente demonstrar um sentimento negativo em relação à experiência.
- Classifique como 'Neutro' se o cliente denotar uma postura neutra.
- Classifique como 'Satisfeito' se o cliente apresentar um sentimento positivo.
- Classifique como 'Muito satisfeito' se o cliente fizer elogios ou manifestações de felicidade.

MENSAGEM DO CLIENTE: "{$user_message}"
PROMPT;
    }
}
