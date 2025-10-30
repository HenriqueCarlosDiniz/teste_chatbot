<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use Illuminate\Support\Facades\Log;

class AwaitingConfirmationState extends AbstractStateHandler implements StateHandler
{
    protected SchedulingService $scheduling_service;

    public function __construct(SchedulingService $scheduling_service)
    {
        $this->scheduling_service = $scheduling_service;
    }

    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        Log::info('[AwaitingConfirmationState] Analisando resposta de confirmação.', ['intent' => $analysis->intent]);

        if ($analysis?->intent === 'afirmativa') {
            $data = $session->state['data'];

            $payload = [
                'name' => $data['user_name'],
                'ddd' => $data['user_ddd'],
                'phone' => $data['user_phone'],
                'date' => $data['chosen_date'],
                'time' => $data['chosen_time'],
                'unit_franchise_group' => $data['chosen_unit']['grupoFranquia'],
                // Adiciona o token de cancelamento se estiver presente na sessão (fluxo de reagendamento)
                'cancellation_token' => $data['cancellation_token_for_reschedule'] ?? null,
            ];

            $result = $this->scheduling_service->criarAgendamento($payload);
            $this->clearConversationState($session);

            $success_message = "Perfeito! Seu agendamento foi confirmado.";
            if (!empty($payload['cancellation_token'])) {
                $success_message = "Perfeito! Seu novo agendamento foi confirmado e o anterior cancelado.";
            }

            return ($result['result'] ?? 0) === 1 ? $success_message : ($result['success'] ?? "Houve um problema ao agendar.");
        }

        if ($analysis?->intent === 'negativa') {
            $this->updateState($session, BookingState::AWAITING_CORRECTION);
            return "Entendido. O que você gostaria de corrigir? (Unidade, data/hora, nome ou telefone)";
        }

        return "Por favor, responda com 'Sim' para confirmar ou 'Não' para corrigir alguma informação.";
    }
}
