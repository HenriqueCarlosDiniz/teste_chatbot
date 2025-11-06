<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Chat\PromptManager;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use Carbon\Carbon; // Importa o Carbon
use Illuminate\Support\Facades\Log; // Importa o Log

class AwaitingDateTimeState extends AbstractStateHandler implements StateHandler
{
    protected PromptManager $prompt_manager;

    public function __construct(PromptManager $prompt_manager)
    {
        $this->prompt_manager = $prompt_manager;
    }

    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        $date_time_data = $this->prompt_manager->extractDateTime($message, $session->id);

        if ($date_time_data === null || empty($date_time_data['date']) || empty($date_time_data['time'])) {
            return "Não consegui entender o dia e o horário. Por favor, tente informar novamente (ex: 'amanhã às 15h').";
        }

        // --- ATUALIZAÇÃO ---
        try {
            // 1. Combina data e hora e salva na coluna dedicada
            $full_datetime = Carbon::parse($date_time_data['date'] . ' ' . $date_time_data['time']);
            $session->selected_datetime = $full_datetime;

            Log::info('[AwaitingDateTimeState] Data/hora salva na sessão.', [
                'session_id' => $session->id,
                'selected_datetime' => $full_datetime->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('[AwaitingDateTimeState] Falha ao processar data/hora.', [
                'session_id' => $session->id,
                'data' => $date_time_data,
                'error' => $e->getMessage()
            ]);
            return "Houve um problema ao processar a data e hora. Por favor, tente informar novamente (ex: 'amanhã às 15h').";
        }

        // 2. Salva no state_data (lógica original) e persiste a sessão
        $this->updateData($session, [
            'chosen_date' => $date_time_data['date'],
            'chosen_time' => $date_time_data['time']
        ]);
        // --- FIM DA ATUALIZAÇÃO ---

        $this->updateState($session, BookingState::AWAITING_NAME);
        return "Entendido. Para continuar, por favor, me informe seu nome completo.";
    }
}