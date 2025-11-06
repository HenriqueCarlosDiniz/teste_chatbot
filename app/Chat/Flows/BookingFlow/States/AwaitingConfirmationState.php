<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // Importa o Carbon

class AwaitingConfirmationState extends AbstractStateHandler implements StateHandler
{
    protected SchedulingService $scheduling_service;

    public function __construct(SchedulingService $scheduling_service)
    {
        $this->scheduling_service = $scheduling_service;
    }

    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        Log::info('[AwaitingConfirmationState] Analisando resposta de confirmação.', ['intent' => $analysis?->intent]);

        if ($analysis?->intent === 'afirmativa') {
            // --- INÍCIO DA ATUALIZAÇÃO ---
            // Lê dados das colunas dedicadas e do JSON de estado
            $data = $session->state['data'] ?? [];

            $nome = $session->customer_name ?? $data['user_name'];
            $telefone_completo = $session->customer_phone ?? $data['full_phone'];
            $datetime = Carbon::parse($session->selected_datetime ?? $data['chosen_date'] . ' ' . $data['chosen_time']);
            $unidade_id = $session->selected_unit_id ?? $data['chosen_unit']['grupoFranquia'];

            // Prepara o payload com os dados corretos
            $payload = [
                'name' => $nome,
                'ddd' => substr($telefone_completo, 0, 2),
                'phone' => substr($telefone_completo, 2),
                'date' => $datetime->format('Y-m-d'),
                'time' => $datetime->format('H:i'),
                'unit_franchise_group' => $unidade_id,
                'cancellation_token' => $data['cancellation_token_for_reschedule'] ?? null,
            ];
            // --- FIM DA ATUALIZAÇÃO ---

            $result = $this->scheduling_service->criarAgendamento($payload);

            // Esta função (definida no AbstractStateHandler) agora também limpa as colunas.
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