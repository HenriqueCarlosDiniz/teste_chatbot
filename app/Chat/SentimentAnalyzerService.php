<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\SentimentAnalysisPrompt;
use Illuminate\Support\Facades\Log;

/**
 * Serviço responsável por analisar e classificar o sentimento de uma mensagem.
 */
class SentimentAnalyzerService
{
    protected AiAdapterInterface $ai_adapter;
    protected SentimentAnalysisPrompt $prompt;

    public function __construct(AiAdapterInterface $ai_adapter, SentimentAnalysisPrompt $prompt)
    {
        $this->ai_adapter = $ai_adapter;
        $this->prompt = $prompt;
    }

    /**
     * Analisa a mensagem do usuário e retorna a classificação de sentimento.
     *
     * @param string $message A mensagem a ser analisada.
     * @param string $session_id O ID da sessão para logging.
     * @return string|null A etiqueta do sentimento ou nulo em caso de falha.
     */
    public function analyze(string $message, string $session_id): ?string
    {
        try {
            $prompt_text = $this->prompt->build($message);
            $raw_response = $this->ai_adapter->getChat($prompt_text, $session_id . '_sentiment');

            $json_data = json_decode(trim($raw_response), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($json_data['label'])) {
                Log::info('[SentimentAnalyzerService] Sentimento classificado com sucesso.', [
                    'session_id' => $session_id,
                    'sentiment' => $json_data['label']
                ]);
                return $json_data['label'];
            }

            Log::warning('[SentimentAnalyzerService] Não foi possível decodificar o JSON da resposta de sentimento.', [
                'session_id' => $session_id,
                'raw_response' => $raw_response
            ]);
        } catch (\Exception $e) {
            Log::error('[SentimentAnalyzerService] Exceção ao analisar sentimento.', [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}
