<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\OrchestratorPrompt;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Exceptions\InvalidDataException;
use PHPUnit\Framework\InvalidDataProviderException;

class ConversationAnalyzerService
{
    public function __construct(
        protected AiAdapterInterface $ai_adapter,
        protected OrchestratorPrompt $prompt
    ) {}

    public function analyze(string $message, ChatSession $session): ConversationAnalysisDTO
    {
        $history = $session->getFormattedHistory();
        $prompt_text = $this->prompt->build($message, $history);

        $raw_response = $this->ai_adapter->getChat($prompt_text, $history, true);

        $json_data = $this->extractJsonFromString($raw_response);

        if (!$json_data) {
            Log::error('[ConversationAnalyzerService] Não foi possível extrair JSON da resposta da IA.', [
                'session_id' => $session->id,
                'raw_response' => $raw_response,
            ]);
            return $this->fallbackResponse();
        }

        try {
            // DTO foi simplificado para corresponder à saída do Orquestrador
            return ConversationAnalysisDTO::from($json_data);
        } catch (InvalidDataException $e) {
            Log::error('[ConversationAnalyzerService] Resposta da IA inválida após a limpeza.', [
                'session_id' => $session->id,
                'raw_response' => $raw_response,
                'cleaned_json' => $json_data,
                'errors' => $e->errors(),
            ]);
            return $this->fallbackResponse();
        }
    }

    private function fallbackResponse(): ConversationAnalysisDTO
    {
        return new ConversationAnalysisDTO(
            intent: 'desconhecida',
            confidence: 0.0
        );
    }

    private function extractJsonFromString(string $string): ?array
    {
        preg_match('/```json\s*(\{.*?\})\s*```/s', $string, $matches);
        $json_string = $matches[1] ?? null;

        if (!$json_string) {
            preg_match('/```\s*(\{.*?\})\s*```/s', $string, $matches);
            $json_string = $matches[1] ?? null;
        }

        if (!$json_string) {
            $json_string = $string;
        }

        $data = json_decode(trim($json_string), true);

        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }
}
