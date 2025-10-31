<?php

namespace App\Adapters\Ai;

use App\Adapters\Ai\AiAdapterInterface;
use App\Data\ConversationAnalysisDTO; // <--- 1. ADICIONE ESTE IMPORT
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Illuminate\Support\Facades\Log;
// Remover os imports incorretos Prism\Prism\Messages, se existirem na versão final.

class PrismAdapter implements AiAdapterInterface
{
    public function getChat(string $prompt, array $history = [], bool $expect_json = false): string
    {
        Log::info('[PrismAdapter] Solicitando chat.', ['expect_json' => $expect_json]);

        // Passo 1: Formatar o histórico para objetos de Mensagem do Prism
        // A função helper é essencial, pois o Prism espera objetos de Mensagem.
        $messages = $this->formatHistoryToPrismMessages($history);

        // O prompt principal é sempre passado como System Prompt para definir o contexto [4].

        if ($expect_json) {
            // Se esperamos JSON, usamos a pipeline "structured" [2], [3].

            // CORREÇÃO: Adicionamos o DTO como o schema esperado
            $response = Prism::structured(ConversationAnalysisDTO::class)
                ->withSystemPrompt($prompt)
                ->withMessages($messages)
                ->asStructured(); // [6]

            // Fallback caso a IA não consiga produzir JSON estruturado (devolve o texto bruto)
            Log::warning('[PrismAdapter] Falha ao obter JSON estruturado. Retornando texto bruto.', ['raw_text' => $response->text]);
            return $response->text ?? '{\"error\": \"Failed to generate structured JSON.\"}';

        }

        // Para texto simples (expect_json = false), usamos a pipeline "text"
        $chat = Prism::text()
            ->withSystemPrompt($prompt)
            ->withMessages($messages); // Adiciona o histórico formatado [7]

        Log::info('[PrismAdapter] Enviando requisição para a LLM.');
        $response = $chat->asText(); // Finaliza a requisição para Text Output [12]

        // Acessa a propriedade 'text' da resposta [13]
        return $response->text;
    }

    /**
     * Formata o array de histórico do ChatSession para o array de
     * objetos de Mensagem que o Prism espera.
     *
     * @param array $history
     * @return array
     */
    private function formatHistoryToPrismMessages(array $history): array
    {
        $messages = [];
        foreach ($history as $message) {
            if ($message['role'] === 'user') {
                $messages[] = new UserMessage($message['content']);
            } elseif ($message['role'] === 'assistant') {
                $messages[] = new AssistantMessage($message['content']);
            }
        }
        return $messages;
    }
}

