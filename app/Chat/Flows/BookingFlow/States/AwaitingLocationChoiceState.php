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

        // Prioridade 1: Escolha por número
        if (is_numeric(trim($message))) {
            $index = intval(trim($message)) - 1;
            if (isset($current_units[$index])) {
                $chosen_unit = $current_units[$index];

                // Salva o ID da unidade na coluna dedicada
                $session->selected_unit_id = $chosen_unit['grupoFranquia'];

                return $this->proceedToDateTime($session, $chosen_unit);
            }
        }

        // --- LÓGICA RESTAURADA ---

        // Prioridade 2: Lógica para escolha por nome ou refinamento (bairro)
        // Usamos o extractEntities, que retorna um "unit_name" (que pode ser um nome ou um bairro)
        // Usamos o histórico para dar contexto à IA
        $entities = $this->prompt_manager->extractEntities($message, $session->id, $session->getFormattedHistory());
        $string_busca = $entities->unit_name; // Ex: "Santana" ou "Pinheiros"

        if ($string_busca) {
            Log::info('[AwaitingLocationChoiceState] Tentando encontrar unidade por nome ou bairro.', ['busca' => $string_busca]);

            $found_by_name = null;
            $refined_units_by_bairro = [];

            foreach ($current_units as $unit) {
                // 1. Verifica se é um match direto com o NOME da unidade
                if (Str::contains(Str::lower($unit['nomeFranquia']), Str::lower($string_busca))) {
                    $found_by_name = $unit;
                    break; // Encontrou um match direto, esta é a prioridade
                }

                // 2. Se não for, verifica se é um match com o BAIRRO (para refinar a lista)
                // Esta é a parte que tinha sido removida
                if (Str::contains(Str::lower($unit['bairroFranquia']), Str::lower($string_busca))) {
                    $refined_units_by_bairro[] = $unit;
                }
            }

            // Se encontrou um match direto com o nome, seleciona
            if ($found_by_name) {
                $session->selected_unit_id = $found_by_name['grupoFranquia'];
                return $this->proceedToDateTime($session, $found_by_name);
            }

            // Se não, se encontrou matches de bairro, refina a lista
            if (!empty($refined_units_by_bairro)) {
                $this->updateData($session, ['unidades_filtradas' => array_values($refined_units_by_bairro)]);
                return $this->formatUnitList($session, $refined_units_by_bairro, "no bairro {$string_busca}");
            }
        }
        // --- FIM DA LÓGICA RESTAURADA ---

        Log::warning('[AwaitingLocationChoiceState] Não foi possível interpretar a escolha do usuário.', ['message' => $message]);
        return "Não consegui entender. Por favor, digite o **número** da unidade que você deseja ou um **bairro/cidade** para refinar a busca.";
    }

    private function proceedToDateTime(ChatSession $session, array $chosen_unit): string
    {
        // Esta função é chamada DEPOIS de $session->selected_unit_id já ter sido definido.
        // O updateData aqui salva o JSON `chosen_unit` e o `selected_unit_id` (do objeto $session)
        // de uma só vez no banco de dados.
        $this->updateData($session, ['chosen_unit' => $chosen_unit]);
        $this->updateState($session, BookingState::AWAITING_DATE_TIME);

        $available_slots = $this->scheduling_service->obterHorariosDisponiveisPorPeriodo($chosen_unit['grupoFranquia']);

        if (empty($available_slots)) {
            Log::warning('[AwaitingLocationChoiceState] Unidade escolhida não possui horários.', ['unidade' => $chosen_unit['nomeFranquia']]);

            // Limpa a escolha
            $session->selected_unit_id = null;
            $this->updateData($session, ['chosen_unit' => null]); // Salva o null (ambos JSON e coluna)
            $this->updateState($session, BookingState::AWAITING_LOCATION_CHOICE);

            return "A unidade *{$chosen_unit['nomeFranquia']}* não tem horários disponíveis. Gostaria de escolher outra?";
        }

        return $this->formatAvailableSlots($available_slots, $chosen_unit['nomeFranquia']);
    }
}