<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Chat\PromptManager;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AwaitingLocationChoiceState extends AbstractStateHandler implements StateHandler
{
    protected PromptManager $prompt_manager;
    protected SchedulingService $scheduling_service;

    public function __construct(PromptManager $prompt_manager, SchedulingService $scheduling_service)
    {
        $this->prompt_manager = $prompt_manager;
        $this->scheduling_service = $scheduling_service;
    }

    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        $current_units = $session->state['data']['unidades_filtradas'] ?? [];
        Log::info('[AwaitingLocationChoiceState] Iniciando handle.', [
            'session_id' => $session->id,
            'message' => $message,
            'unidades_em_contexto' => count($current_units)
        ]);

        // Lógica para escolha por número (mais rápido e direto)
        if (is_numeric(trim($message))) {
            $index = intval(trim($message)) - 1;
            if (isset($current_units[$index])) {
                Log::info('[AwaitingLocationChoiceState] Usuário escolheu por número.', ['index' => $index, 'unidade' => $current_units[$index]['nomeFranquia']]);
                return $this->proceedToDateTime($session, $current_units[$index]);
            }
        }

        // Nova lógica: Usa o extrator de entidades para entender a escolha
        $entities = $this->prompt_manager->extractEntities($message, $session->id, $session->getFormattedHistory());
        Log::info('[AwaitingLocationChoiceState] Entidades extraídas da mensagem.', ['entities' => $entities?->toArray()]);

        $chosen_unit_name = $entities->unit_name ?? $entities->location_value; // Pode ser nome da unidade ou um bairro para refinar

        if ($chosen_unit_name) {
            $normalized_choice = Str::ascii(Str::lower($chosen_unit_name));
            Log::info('[AwaitingLocationChoiceState] Tentando encontrar unidade por nome/localização.', ['escolha_normalizada' => $normalized_choice]);

            // Tenta encontrar a unidade exata pelo nome
            foreach ($current_units as $unit) {
                $franchise_name = $unit['nomeFranquia'];
                $normalized_franchise_name = Str::ascii(Str::lower($franchise_name));
                if (Str::contains($normalized_franchise_name, $normalized_choice)) {
                    Log::info('[AwaitingLocationChoiceState] Unidade correspondente encontrada.', ['unidade' => $franchise_name]);
                    return $this->proceedToDateTime($session, $unit);
                }
            }

            Log::info('[AwaitingLocationChoiceState] Nenhuma unidade exata encontrada. Verificando se é um refinamento de busca.');
            // CORREÇÃO: Se não encontrou uma unidade, verifica se o usuário
            // tentou refinar a busca com um bairro ou cidade.
            if ($entities->location_type === 'neighborhood' || $entities->location_type === 'city') {
                // Filtra a lista atual de unidades com o novo critério.
                $bairro_filtro = $entities->location_type === 'neighborhood' ? $chosen_unit_name : null;
                $cidade_filtro = $entities->location_type === 'city' ? $chosen_unit_name : null;

                Log::info('[AwaitingLocationChoiceState] Refinando busca com novos critérios.', ['bairro' => $bairro_filtro, 'cidade' => $cidade_filtro]);
                $refined_units = $this->scheduling_service->filtrarUnidades($current_units, bairro: $bairro_filtro, cidade: $cidade_filtro);
                Log::info('[AwaitingLocationChoiceState] Resultado do refinamento.', ['unidades_encontradas' => count($refined_units)]);

                if (!empty($refined_units)) {
                    // Apresenta a nova lista refinada para o usuário.
                    return $this->formatUnitList($session, $refined_units, "em {$chosen_unit_name}");
                }
            }
        }

        Log::warning('[AwaitingLocationChoiceState] Não foi possível interpretar a escolha do usuário.', ['message' => $message]);
        return "Não consegui entender. Por favor, digite o **número** da unidade que você deseja ou um **bairro** para refinar a busca.";
    }

    private function proceedToDateTime(ChatSession $session, array $chosen_unit): string
    {
        $this->updateData($session, ['chosen_unit' => $chosen_unit]);
        $this->updateState($session, BookingState::AWAITING_DATE_TIME);
        $available_slots = $this->scheduling_service->obterHorariosDisponiveisPorPeriodo($chosen_unit['grupoFranquia']);

        if (empty($available_slots)) {
            Log::warning('[AwaitingLocationChoiceState] Unidade escolhida não possui horários.', ['unidade' => $chosen_unit['nomeFranquia']]);
            $this->updateState($session, BookingState::AWAITING_LOCATION_CHOICE);
            $this->updateData($session, ['chosen_unit' => null]);
            return "A unidade *{$chosen_unit['nomeFranquia']}* não tem horários disponíveis. Gostaria de escolher outra?";
        }

        return $this->formatAvailableSlots($available_slots, $chosen_unit['nomeFranquia']);
    }
}
