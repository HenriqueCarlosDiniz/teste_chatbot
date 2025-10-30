<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\GeneralIntentPrompt;
use Illuminate\Support\Facades\Log;

/**
 * Classifica a intenção geral de uma mensagem do utilizador usando um modelo de IA.
 */
class GeneralIntentClassifier
{
    protected AiAdapterInterface $ai;
    protected GeneralIntentPrompt $prompt;

    public function __construct(AiAdapterInterface $ai, GeneralIntentPrompt $prompt)
    {
        $this->ai = $ai;
        $this->prompt = $prompt;
    }

    /**
     * Usa o adaptador de IA para classificar a intenção da mensagem.
     *
     * @param string $message A mensagem do utilizador.
     * @param string $sessionId O ID da sessão para manter o contexto.
     * @return string A intenção classificada.
     */
    public function classify(string $message, string $sessionId): string
    {
        Log::info('[GeneralIntentClassifier] Iniciando classificação de intenção.', [
            'session_id' => $sessionId,
            'message' => $message
        ]);

        $promptText = $this->prompt->build($message);
        $response = $this->ai->getChat($promptText, $sessionId);
        $intent = $this->parseIntentFromResponse($response);

        Log::info('[GeneralIntentClassifier] Classificação concluída.', [
            'session_id' => $sessionId,
            'raw_response' => $response,
            'parsed_intent' => $intent
        ]);

        return $intent;
    }

    /**
     * Analisa a resposta da IA para extrair a intenção.
     *
     * @param string $response A resposta bruta do modelo de IA.
     * @return string
     */
    protected function parseIntentFromResponse(string $response): string
    {
        $cleanedResponse = strtolower(trim($response));

        // CORRIGIDO: A lista de intenções válidas agora inclui todas as
        // possibilidades definidas no GeneralIntentPrompt.
        $validIntents = ['agendamento', 'cancelamento', 'informacao_geral', 'saudacao'];

        if (in_array($cleanedResponse, $validIntents)) {
            return $cleanedResponse;
        }

        Log::warning('[GeneralIntentClassifier] A resposta da IA não corresponde a uma intenção válida.', [
            'response' => $cleanedResponse,
            'valid_intents' => $validIntents
        ]);

        return 'unknown';
    }
}
