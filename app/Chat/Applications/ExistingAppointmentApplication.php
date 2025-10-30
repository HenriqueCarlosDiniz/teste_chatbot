<?php

namespace App\Chat\Applications;

use App\Chat\Contracts\ChatApplicationInterface;
use App\Chat\Enums\BookingState;
use App\Chat\PromptManager;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExistingAppointmentApplication implements ChatApplicationInterface
{
    protected SchedulingService $scheduling_service;
    protected PromptManager $prompt_manager;

    public function __construct(
        SchedulingService $scheduling_service,
        PromptManager $prompt_manager
    ) {
        $this->scheduling_service = $scheduling_service;
        $this->prompt_manager = $prompt_manager;
    }

    public function shouldHandle(string $message, ChatSession $session): bool
    {
        if (($session->state['flow'] ?? null) === 'existing_appointment') {
            return true;
        }

        $management_intent = $this->prompt_manager->extractAppointmentManagementIntent($message, $session->id);

        if (in_array($management_intent, ['confirmar', 'cancelar', 'reagendar'])) {
            Log::info('[ExistingAppointmentApplication] Detectou intenção de gerenciamento.', ['intent' => $management_intent]);
            $state = $session->state ?? [];
            $state['pending_management_intent'] = $management_intent;
            $session->state = $state;
            $session->save();
            return true;
        }

        if (empty($session->state) && $session->phone_number) {
            $appointment = $this->scheduling_service->buscarAgendamentoPorTelefone($session->phone_number);
            return !empty($appointment) && ($appointment['sucesso'] ?? false);
        }

        return false;
    }

    public function handle(string $message, ChatSession $session): string
    {
        $state = $session->state ?? [];
        if (($state['flow'] ?? null) !== 'existing_appointment') {
            $state['flow'] = 'existing_appointment';
        }

        if (isset($state['pending_management_intent'])) {
            $intent = $state['pending_management_intent'];
            unset($state['pending_management_intent']);
            $session->state = $state;
            $session->save();

            $appointment = $this->scheduling_service->buscarAgendamentoPorTelefone($session->phone_number);

            if (empty($appointment) || !($appointment['sucesso'] ?? false)) {
                $session->state = null;
                $session->save();
                return "Você tentou {$intent} um agendamento, mas não encontrei nenhum no seu número. Gostaria de marcar um novo?";
            }

            switch ($intent) {
                case 'confirmar':
                    return $this->confirmAppointment($session, $appointment);
                case 'cancelar':
                    return $this->cancelAppointment($session, $appointment);
                case 'reagendar':
                    return $this->rescheduleAppointment($session, $appointment);
            }
        }

        $flow_state = $session->state['flow_state'] ?? 'STARTED';

        if ($flow_state === 'AWAITING_CONFIRMATION') {
            return $this->handleConfirmationAction($message, $session);
        }

        return $this->findAndPresentAppointment($session);
    }

    private function handleConfirmationAction(string $message, ChatSession $session): string
    {
        $appointment_details = $session->state['data']['appointment_details'] ?? null;
        $action = $this->prompt_manager->extractExistingAppointmentAction($message, $session->id);

        Log::info('[ExistingAppointmentApplication] Ação do usuário classificada.', ['action' => $action]);

        switch ($action) {
            case 'confirmar':
                return $this->confirmAppointment($session, $appointment_details);
            case 'cancelar':
                return $this->cancelAppointment($session, $appointment_details);
            case 'reagendar':
                return $this->rescheduleAppointment($session, $appointment_details);
            default:
                return 'Desculpe, não entendi. Você gostaria de *confirmar*, *cancelar* ou *reagendar* o seu agendamento?';
        }
    }

    private function confirmAppointment(ChatSession $session, ?array $appointment_details): string
    {
        if ($appointment_details['confirmado'] ?? false) {
            return "Este agendamento já foi confirmado anteriormente. Se desejar, pode *cancelar* ou *reagendar*.";
        }
        $token = $appointment_details['token_confirmacao'] ?? null;
        if (!$token) return 'Não encontrei as informações para confirmar. Por favor, contacte o suporte.';

        $result = $this->scheduling_service->confirmarAgendamento($token);
        $session->state = null;
        $session->save();
        return $result['resposta']['sucesso']
            ? 'Ótimo! O seu agendamento foi confirmado com sucesso.'
            : 'Houve um problema ao tentar confirmar o seu agendamento. Por favor, tente novamente mais tarde.';
    }

    private function cancelAppointment(ChatSession $session, ?array $appointment_details): string
    {
        $token = $appointment_details['token_cancelamento'] ?? null;
        if (!$token) return 'Não encontrei as informações para cancelar. Por favor, contacte o suporte.';

        $result = $this->scheduling_service->cancelarAgendamento($token);
        $session->state = null;
        $session->save();
        Log::info('[ExistingAppointmentApplication] Agendamento cancelado.', ['result' => $result]);
        return $result['resposta']['sucesso']
            ? 'O seu agendamento foi cancelado. Se precisar de mais alguma coisa, é só chamar.'
            : 'Houve um problema ao tentar cancelar o seu agendamento. Por favor, tente novamente mais tarde.';
    }

    private function rescheduleAppointment(ChatSession $session, ?array $appointment_details): string
    {
        $cancellation_token = $appointment_details['token_cancelamento'] ?? null;

        if ($cancellation_token) {
            $this->scheduling_service->cancelarAgendamento($cancellation_token);
            Log::info('[ExistingAppointmentApplication] Agendamento anterior cancelado para reagendamento.');
        }

        $session->state = [
            'flow' => 'booking',
            'flow_state' => BookingState::INITIAL,
            'data' => [
                'cancellation_token_for_reschedule' => $cancellation_token
            ]
        ];
        $session->save();

        Log::info('[ExistingAppointmentApplication] Iniciando fluxo de reagendamento.', ['cancellation_token' => $cancellation_token]);

        return "Certo, vamos reagendar. Para começarmos, por favor, me informe o estado ou cidade onde deseja o atendimento.";
    }

    private function findAndPresentAppointment(ChatSession $session): string
    {
        $phone_number = $session->phone_number;
        $appointment = $this->scheduling_service->buscarAgendamentoPorTelefone($phone_number);

        if (empty($appointment) || !($appointment['sucesso'] ?? false)) {
            $session->state = null;
            $session->save();
            return app(GreetingApplication::class)->handle('', $session);
        }

        $atendimento_carbon = Carbon::parse($appointment['atendimento_em']);
        $date = $atendimento_carbon->translatedFormat('d \d\e F \d\e Y');
        $time = $atendimento_carbon->format('H:i');

        $response = "Olá! Vi que você tem um agendamento para o dia *{$date} às {$time}h*.";

        if ($appointment['confirmado']) {
            $response .= "\nEste agendamento já está *confirmado*.";
            $response .= "\n\nGostaria de *cancelar* ou *reagendar*?";
        } else {
            $response .= "\nEle *ainda não foi confirmado*.";
            $response .= "\n\nGostaria de *confirmar*, *cancelar* ou *reagendar*?";
        }

        $state = $session->state;
        $state['flow_state'] = 'AWAITING_CONFIRMATION';
        $state['data']['appointment_details'] = $appointment;
        $session->state = $state;
        $session->save();

        return $response;
    }
}
