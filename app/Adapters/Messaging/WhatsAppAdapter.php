<?php

namespace App\Adapters\Messaging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class WhatsAppAdapter
 *
 * Adaptador para a API do WhatsApp Cloud.
 */
class WhatsAppAdapter implements MessagingAdapterInterface
{
    protected string $token;
    protected string $phone_numberId;
    protected string $apiUrl;

    /**
     * Carrega as configurações da API do WhatsApp.
     */
    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->phone_numberId = config('services.whatsapp.phone_number_id');
        $this->apiUrl = "https://graph.facebook.com/v19.0/{$this->phone_numberId}/messages";
    }

    /**
     * Processa a requisição de entrada do webhook do WhatsApp.
     *
     * @param Request $request
     * @return array
     */
    public function processIncoming(Request $request): array
    {
        // Extrai a mensagem de texto do payload do webhook.
        $message = $request->input('entry.0.changes.0.value.messages.0.text.body');
        $senderId = $request->input('entry.0.changes.0.value.messages.0.from');

        if (!$message || !$senderId) {
            // Se não for uma mensagem de texto válida, retorna um array vazio.
            return [];
        }

        return [
            'senderId' => $senderId,
            'message' => $message,
        ];
    }

    /**
     * Envia uma mensagem de resposta para o usuário via WhatsApp.
     *
     * @param string $recipientId O número de telefone do destinatário.
     * @param string $message A mensagem a ser enviada.
     * @return bool
     */
    public function sendResponse(string $recipientId, string $message): bool
    {
        if (empty($this->token) || empty($this->phone_numberId)) {
            Log::error('Configurações do WhatsApp não definidas (token ou phone_number_id).');
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'text' => ['body' => $message],
        ];

        $response = Http::withToken($this->token)->post($this->apiUrl, $payload);

        if ($response->failed()) {
            Log::error('Falha ao enviar mensagem pelo WhatsApp: ' . $response->body());
            return false;
        }

        return true;
    }
}
