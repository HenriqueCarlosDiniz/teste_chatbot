<?php

namespace App\Chat\Applications;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Contracts\ChatApplicationInterface;
use App\Chat\Flows\BookingFlowManager;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use Illuminate\Support\Facades\Log;

class BookingApplication implements ChatApplicationInterface
{
    protected AiAdapterInterface $ai_adapter;
    protected SchedulingService $scheduling_service;
    protected bool $unidades_disponiveis = true;

    public function __construct(AiAdapterInterface $ai_adapter, SchedulingService $scheduling_service)
    {
        $this->ai_adapter = $ai_adapter;
        $this->scheduling_service = $scheduling_service;
    }

    public function shouldHandle(string $message, ChatSession $session): bool
    {
        Log::info('[BookingApplication] Verificando se deve manipular a mensagem.');

        $unidades = $this->scheduling_service->getUnidades();
        if (empty($unidades)) {
            Log::warning('[BookingApplication] Cache de unidades vazio. O serviço está indisponível.');
            $this->unidades_disponiveis = false;
            // Mesmo indisponível, deve manipular para informar o usuário.
            return true;
        }

        $state = $session->state ?? [];
        if (($state['flow'] ?? null) === 'booking') {
            Log::info('[BookingApplication] Sessão já está no fluxo de agendamento.');
            return true;
        }

        $prompt = "A mensagem do usuário demonstra a intenção de agendar, marcar, reservar ou verificar a disponibilidade de um serviço? Responda apenas com 'sim' ou 'não'. Mensagem: \"$message\"";
        $response = trim(strtolower($this->ai_adapter->getChat($prompt, $session->id)));

        $should_handle = $response === 'sim';
        Log::info('[BookingApplication] Verificação de intenção de agendamento.', ['response' => $response, 'should_handle' => $should_handle]);

        return $should_handle;
    }

    public function handle(string $message, ChatSession $session): string
    {
        Log::info('[BookingApplication] Manipulando a mensagem.');

        if (!$this->unidades_disponiveis) {
            return "Peço desculpas, mas nosso sistema de agendamento está temporariamente indisponível. Por favor, tente novamente mais tarde.";
        }

        $state = $session->state ?? [];
        // Inicia o fluxo de agendamento se ainda não estiver nele.
        if (($state['flow'] ?? null) !== 'booking') {
            $state['flow'] = 'booking';
            // O estado inicial do fluxo é definido pelo próprio manipulador de estado.
            unset($state['flow_state']);
            $session->state = $state;
            $session->save();
            Log::info('[BookingApplication] Contexto da sessão definido para "booking".');
        }

        // Cria uma instância do BookingFlowManager passando a sessão específica.
        $booking_flow_manager = app()->make(BookingFlowManager::class, ['session' => $session]);

        return $booking_flow_manager->handle($message);
    }
}
