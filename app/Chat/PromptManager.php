<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\EntityExtractorPrompt;
use App\Data\EntityExtractionDTO;
use Illuminate\Support\Facades\Log;
use App\Models\ChatSession;
use Spatie\LaravelData\Exceptions\InvalidDataException;
use App\Chat\Prompts\ExistingAppointmentActionPrompt;
use App\Chat\Prompts\ExtractAppointmentManagementIntentPrompt;
use App\Chat\Prompts\ExtractCorrectionPrompt;
use App\Chat\Prompts\ExtractDateTimePrompt;
use App\Chat\Prompts\ExtractFilterCriteriaPrompt;
use App\Chat\Prompts\UnitChoicePrompt;


/**
 * Centraliza a lógica de construção de prompts e interação com a IA para extração de dados.
 */
class PromptManager
{
    protected AiAdapterInterface $ai_adapter;

    public function __construct(AiAdapterInterface $ai_adapter)
    {
        $this->ai_adapter = $ai_adapter;
    }

    /**
     * Usa o EntityExtractorPrompt para extrair várias entidades de uma mensagem.
     */
    public function extractEntities(string $message, ChatSession $session): ?EntityExtractionDTO
    {
        $history_array = $session->getHistoryAsArray();
        $prompt = app(EntityExtractorPrompt::class)->build($message);
        $response_json = $this->ai_adapter->getChat(
            $prompt,
            $history_array,
            true // Pede JSON
        );

        $data = json_decode(trim($response_json), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('[PromptManager] Resposta da IA para extração de entidades não é um JSON válido.', [
                'session_id' => $session->id,
                'response' => $response_json
            ]);
            return null;
        }

        try {
            return EntityExtractionDTO::from($data);
        } catch (InvalidDataException $e) {
            Log::error('[PromptManager] Erro ao criar EntityExtractionDTO.', [
                'session_id' => $session->id,
                'data' => $data,
                'errors' => $e->errors()
            ]);
            return null;
        }
    }


    /**
     * Extrai a data e a hora da mensagem do usuário.
     */
    public function extractDateTime(string $message, ChatSession $session): ?array
    {
        $prompt = app(ExtractDateTimePrompt::class)->build($message);
        $response_json = $this->ai_adapter->getChat(
            $prompt,
            $session->getHistoryAsArray(),
            true // Pede JSON
        );
        $data = json_decode(trim($response_json), true);

        return (json_last_error() === JSON_ERROR_NONE && !empty($data['date']) && !empty($data['time'])) ? $data : null;
    }

    /**
     * Identifica qual unidade o usuário escolheu de uma lista.
     */
    public function extractUnitChoice(string $message, array $units, ChatSession $session): ?array
    {
        $prompt = app(UnitChoicePrompt::class)->build($message, $units);
        $response_json = $this->ai_adapter->getChat(
            $prompt,
            $session->getHistoryAsArray(),
            true // Pede JSON
        );
        $data = json_decode(trim($response_json), true);

        Log::info('[PromptManager] Resposta da IA para escolha de unidade.', ['response' => $data]);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }

    /**
     * Extrai qual campo o usuário deseja corrigir.
     */
    public function extractCorrectionField(string $message, ChatSession $session): string
    {
        $prompt = app(ExtractCorrectionPrompt::class)->build($message);
        $response = $this->ai_adapter->getChat(
            $prompt,
            $session->getHistoryAsArray(),
            false // Pede TEXTO
        );
        return trim(strtolower($response));
    }

    /**
     * Extrai critérios de filtro de uma pergunta sobre unidades.
     */
    public function extractFilterCriteria(string $message, ChatSession $session): ?array
    {
        $prompt = app(ExtractFilterCriteriaPrompt::class)->build($message);
        $response_json = $this->ai_adapter->getChat(
            $prompt,
            $session->getHistoryAsArray(),
            true // Pede JSON
        );
        $criteria = json_decode(trim($response_json), true);

        return (json_last_error() === JSON_ERROR_NONE && !empty(array_filter($criteria))) ? $criteria : null;
    }

    /**
     * Extrai a ação do usuário sobre um agendamento existente.
     */
    public function extractExistingAppointmentAction(string $message, ChatSession $session): string
    {
        $prompt = app(ExistingAppointmentActionPrompt::class)->build($message);
        $response_json = $this->ai_adapter->getChat(
            $prompt,
            $session->getHistoryAsArray(),
            true // Pede JSON
        );
        $choice_data = json_decode(trim($response_json), true);
        return $choice_data['action'] ?? 'desconhecido';
    }

    /**
     * Extrai se a intenção é gerenciar um agendamento (confirmar, cancelar, reagendar).
     */
    public function extractAppointmentManagementIntent(string $message, ChatSession $session): string
    {
        $prompt = app(ExtractAppointmentManagementIntentPrompt::class)->build($message);
        $response = $this->ai_adapter->getChat(
            $prompt,
            $session->getHistoryAsArray(),
            false // Pede TEXTO
        );
        $cleaned_response = trim(strtolower($response));

        if (in_array($cleaned_response, ['confirmar', 'cancelar', 'reagendar', 'nenhuma'])) {
            return $cleaned_response;
        }

        return 'nenhuma';
    }
}
