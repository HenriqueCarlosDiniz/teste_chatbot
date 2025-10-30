<?php

namespace App\Chat;

use App\Chat\Applications\BookingApplication;
use App\Chat\Applications\ExistingAppointmentApplication;
use App\Chat\Contracts\ChatApplicationInterface;
use App\Models\ChatSession;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

class ChatApplicationManager
{
    protected Container $container;
    protected ConversationAnalyzerService $conversation_analyzer;

    public function __construct(Container $container, ConversationAnalyzerService $conversation_analyzer)
    {
        $this->container = $container;
        $this->conversation_analyzer = $conversation_analyzer;
    }

    public function handle(ChatSession $session, string $message, string $channel): ?string
    {
        Log::info('[Manager] Iniciando handle', ['session_id' => $session->id, 'message' => $message]);

        // Prioridade 1: Manter o fluxo de uma aplicação de MÚLTIPLOS PASSOS já ativa.
        $active_app = $this->getActiveStatefulApplication($session);
        if ($active_app) {
            $app_name = get_class($active_app);
            Log::info("[Manager] Aplicação com estado ativa encontrada: {$app_name}. Delegando o controle.");
            return $active_app->handle($message, $session);
        }

        // Prioridade 2: Orquestrar uma nova intenção se nenhum fluxo estiver ativo.
        $analysis = $this->conversation_analyzer->analyze($message, $session);
        Log::info("[Manager] Intenção classificada pelo orquestrador: {$analysis->intent}");

        $application = $this->getApplicationForIntent($analysis->intent);

        if ($application) {
            $app_name = get_class($application);
            Log::info("[Manager] Aplicação correspondente à intenção encontrada: {$app_name}.");
            return $application->handle($message, $session);
        }

        Log::warning('[Manager] Nenhuma aplicação foi encontrada para tratar a intenção.', [
            'intent' => $analysis->intent,
            'message' => $message
        ]);
        return 'Desculpe, não entendi. Eu posso te ajudar a agendar, consultar ou cancelar uma avaliação. Como posso prosseguir?';
    }

    /**
     * Verifica se existe um fluxo de múltiplos passos ativo na sessão.
     * Aplicações sem estado (como saudação) são ignoradas aqui.
     */
    protected function getActiveStatefulApplication(ChatSession $session): ?ChatApplicationInterface
    {
        $flow = $session->state['flow'] ?? null;

        // Mapeia os nomes dos fluxos que devem persistir entre as mensagens.
        $stateful_flows = [
            'booking' => BookingApplication::class,
            'existing_appointment' => ExistingAppointmentApplication::class,
        ];

        if ($flow && isset($stateful_flows[$flow])) {
            $class = $stateful_flows[$flow];
            if ($this->container->has($class)) {
                return $this->container->make($class);
            }
        }

        return null;
    }

    /**
     * Mapeia uma intenção para a classe de aplicação correspondente.
     */
    protected function getApplicationForIntent(string $intent): ?ChatApplicationInterface
    {
        // Mapeia todas as intenções, incluindo as de passo único.
        $intent_map = [
            'agendamento' => \App\Chat\Applications\BookingApplication::class,
            'consultar_agendamento' => \App\Chat\Applications\ExistingAppointmentApplication::class,
            'cancelamento' => \App\Chat\Applications\ExistingAppointmentApplication::class,
            'reagendamento' => \App\Chat\Applications\ExistingAppointmentApplication::class,
            'saudacao' => \App\Chat\Applications\GreetingApplication::class,
        ];

        $class = $intent_map[$intent] ?? null;

        if ($class && $this->container->has($class)) {
            return $this->container->make($class);
        }

        return null;
    }
}
