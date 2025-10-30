<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Adapters\Ai\AiAdapterInterface;
use App\Adapters\Messaging\MessagingAdapterInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class WebhookController
 *
 * Ponto de entrada para todas as requisições de webhooks das plataformas de mensagens.
 */
class WebhookController extends Controller
{
    /**
     * Manipula a requisição de entrada do webhook.
     */
    public function handle(
        Request $request,
        AiAdapterInterface $aiAdapter,
        MessagingAdapterInterface $messagingAdapter
    ): JsonResponse {
        try {
            // Passo 1: O adaptador de mensagens processa a requisição bruta
            $processedMessage = $messagingAdapter->processIncoming($request);

            if (empty($processedMessage['message'])) {
                Log::info('Requisição de webhook ignorada (sem mensagem de texto).');
                return response()->json(['status' => 'ignored', 'reason' => 'No text message found']);
            }

            // Passo 2: A mensagem padronizada é enviada para o adaptador de IA
            // MODIFICADO: Utiliza o método getChat, passando o senderId como sessionId
            $aiResponse = $aiAdapter->getChat($processedMessage['message'], $processedMessage['senderId']);

            // Passo 3: A resposta da IA é enviada de volta para o usuário
            $messagingAdapter->sendResponse($processedMessage['senderId'], $aiResponse);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Erro no WebhookController: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Ocorreu um erro interno.'], 500);
        }
    }
}
