<?php

namespace App\Adapters\Ai;

use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

class PrismAdapter implements AiAdapterInterface
{
    public function getChat(string $prompt, array $history = [], bool $expect_json = false): string
    {
        // Constrói a chamada base do Prism
        // Você pode tornar o provedor e o modelo configuráveis (via config/prism.php)
        $chat = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o') // Ou qualquer modelo que preferir
            ->withSystemPrompt($prompt);

        // Adiciona o histórico, se houver
        if (!empty($history)) {
            // O Prism pode precisar que o histórico seja formatado
            // (ex: $chat->withHistory($this->formatHistory($history)))
            // Por enquanto, vamos assumir que `withMessages` funcione
            // Nota: Verifique a documentação do Prism para a melhor forma de
            // injetar o histórico (ex: `->withMessages([...])`)
            $chat->withMessages($history); // Ajuste conforme a API do Prism
        }

        if ($expect_json) {
            // É AQUI QUE O PRISM RESOLVE SEU PROBLEMA
            // Ele lidará com a formatação da requisição para forçar um JSON
            $response = $chat->asJson();
            return $response->content(); // ou $response->json()
        }

        // Para texto simples (ex: "sim", "não")
        $response = $chat->asText();
        return $response->text(); // ou $response->content()
    }

    /*
    // Você pode precisar de um helper para formatar o histórico
    // para o formato que o Prism espera
    private function formatHistory(array $history): array
    {
        $formatted = [];
        foreach ($history as $message) {
            if ($message['role'] === 'user') {
                $formatted[] = \Prism\Prism\Messages\UserMessage::from($message['content']);
            } elseif ($message['role'] === 'model') {
                $formatted[] = \Prism\Prism\Messages\AssistantMessage::from($message['content']);
            }
        }
        return $formatted;
    }
    */
}
