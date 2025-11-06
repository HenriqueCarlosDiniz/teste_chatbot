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

        // --- INÍCIO DA NOVA LÓGICA ---
        // Prioridade 1.5: Verificar agendamentos existentes NO INÍCIO da conversa (se tiver telefone)
        // Verificamos se $session->state está vazio (conversa nova) E se temos um phone_number
        if (empty($session->state) && $session->phone_number) {
            Log::info('[Manager] Sessão nova com telefone. Verificando agendamentos existentes.', ['phone' => $session->phone_number]);

            // Vamos instanciar o SchedulingService para verificar
            $scheduling_service = $this->container->make(SchedulingService::class);
            $appointment = $scheduling_service->buscarAgendamentoPorTelefone($session->phone_number);

            if (!empty($appointment) && ($appointment['sucesso'] ?? false)) {
                Log::info('[Manager] Agendamento existente encontrado. Iniciando ExistingAppointmentApplication.', ['phone' => $session->phone_number]);

                // Encontrámos um agendamento. Vamos forçar o início da aplicação correta.
                // A "ExistingAppointmentApplication" é mapeada para estas intenções.
                $application = $this->getApplicationForIntent('consultar_agendamento');
                if ($application) {
                    // O "handle" desta aplicação irá encontrar e apresentar o agendamento
                    return $application->handle($message, $session);
                }
            } else {
                 Log::info('[Manager] Nenhum agendamento encontrado para este telefone. Continuando fluxo normal.', ['phone' => $session->phone_number]);
            }
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
        $active_class = $session->current_application;

        if ($active_class) {
            Log::info("[Manager] Verificando aplicação ativa pela coluna 'current_application'.", ['class' => $active_class]);

            $stateful_classes = [
                BookingApplication::class,
                ExistingAppointmentApplication::class,
            ];

            if (in_array($active_class, $stateful_classes) && $this->container->has($active_class)) {
                return $this->container->make($active_class);
            }
        }

        $flow = $session->state['flow'] ?? null;
        // Fallback (Prioridade 2): Verificar a lógica antiga (state['flow'])
        // Isto mantém a compatibilidade com o ExistingAppointmentApplication

        if ($flow) {
            Log::info("[Manager] Verificando aplicação ativa pela chave antiga 'state[flow]'.", ['flow' => $flow]);

            $stateful_flows = [
                'booking' => BookingApplication::class,
                'existing_appointment' => ExistingAppointmentApplication::class,
            ];

            if (isset($stateful_flows[$flow])) {
                $class_from_flow = $stateful_flows[$flow];
                if ($this->container->has($class_from_flow)) {
                    // Sincroniza a nova coluna para migrar da lógica antiga
                    $session->current_application = $class_from_flow;
                    $session->save();
                    return $this->container->make($class_from_flow);
                }
            }
        }

        Log::info('[Manager] Nenhuma aplicação com estado ativa foi encontrada.');
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
