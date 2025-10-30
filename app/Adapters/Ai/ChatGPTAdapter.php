<?php

namespace App\Adapters\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class ChatGPTAdapter
 *
 * Implementação do AiAdapterInterface para o serviço ChatGPT da OpenAI.
 */
class ChatGPTAdapter implements AiAdapterInterface
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4.1-mini');
    }

    /**
     * Envia um prompt para a API do ChatGPT e retorna a resposta.
     *
     * @param string $prompt O prompt completo a ser enviado para a IA.
     * @param string $sessionId O ID da sessão (não usado diretamente pelo ChatGPT neste modelo simples, mas necessário para cumprir a interface).
     * @param array $context O histórico da conversa.
     * @return string A resposta de texto gerada pela IA.
     */
    public function getChat(string $prompt, string $sessionId, array $context = []): string
    {
        if (empty($this->apiKey)) {
            Log::error('OpenAI API key não está configurada.');
            return 'Erro de configuração: A chave da API não foi definida.';
        }

        $messages = $context;
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post($this->apiUrl, $payload);

        if ($response->successful()) {
            return $response->json('choices.0.message.content', '{"intent": "desconhecida", "confidence": 0.0}');
        }

        Log::error('Falha na API da OpenAI: ' . $response->body(), [
            'status' => $response->status(),
            'payload_sent' => $payload
        ]);

        return 'Desculpe, não consegui processar sua solicitação no momento. Tente novamente mais tarde.';
    }
}
