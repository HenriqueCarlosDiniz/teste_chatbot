<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Chat\PromptManager;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use Illuminate\Support\Facades\Log;

class AwaitingCorrectionState extends AbstractStateHandler implements StateHandler
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
        $correction_field = $this->prompt_manager->extractCorrectionField($message, $session->id);
        Log::info('[AwaitingCorrectionState] IA identificou campo para correção.', ['campo' => $correction_field]);
        $data = $session->state['data'];

        switch ($correction_field) {
            case 'unidade':
                unset($data['chosen_unit'], $data['unidades_filtradas'], $data['criterios_atuais']);
                $this->updateData($session, $data);
                $this->updateState($session, BookingState::AWAITING_LOCATION);
                return "Ok, vamos corrigir a unidade. Por favor, informe novamente a cidade ou estado.";

            case 'data':
                unset($data['chosen_date'], $data['chosen_time']);
                $this->updateData($session, $data);
                $this->updateState($session, BookingState::AWAITING_DATE_TIME);
                $horarios = $this->scheduling_service->obterHorariosDisponiveisPorPeriodo($data['chosen_unit']['grupoFranquia']);
                return "Certo, vamos alterar a data e hora. " . $this->formatAvailableSlots($horarios, $data['chosen_unit']['nomeFranquia']);

            case 'nome':
                unset($data['user_name']);
                $this->updateData($session, $data);
                $this->updateState($session, BookingState::AWAITING_NAME);
                return "Entendido. Por favor, informe o seu nome completo correto.";

            case 'telefone':
                unset($data['user_phone'], $data['user_ddd'], $data['full_phone']);
                $this->updateData($session, $data);
                $this->updateState($session, BookingState::AWAITING_PHONE);
                return "Ok. Por favor, informe o número de telefone correto, com DDD.";

            default:
                return "Não entendi o que precisa ser corrigido. Por favor, diga se o erro está na 'unidade', 'data', 'nome' ou 'telefone'.";
        }
    }
}
