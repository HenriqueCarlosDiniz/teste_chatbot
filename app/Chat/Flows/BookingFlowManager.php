<?php

namespace App\Chat\Flows;

use App\Chat\ConversationAnalyzerService;
use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\StateHandlerFactory;
use App\Models\ChatSession;

/**
 * Class BookingFlowManager (Refatorado)
 * Orquestra o fluxo de agendamento, delegando a lógica para manipuladores de estado específicos.
 */
class BookingFlowManager
{
    protected StateHandlerFactory $state_handler_factory;
    protected ChatSession $session;

    public function __construct(StateHandlerFactory $state_handler_factory, ChatSession $session)
    {
        $this->state_handler_factory = $state_handler_factory;
        $this->session = $session;
    }

    /**
     * Ponto de entrada para manipular a mensagem do usuário dentro do fluxo de agendamento.
     */
    public function handle(string $message): string
    {
        // Obtém o estado atual da sessão, ou define como INITIAL se não existir.
        $current_state = $this->session->state['flow_state'] ?? BookingState::INITIAL;

        // Analisa a mensagem para extrair intenção, localização, etc.
        $analyzer = app(ConversationAnalyzerService::class);
        $analysis = $analyzer->analyze($message, $this->session);

        // Usa a fábrica para obter o manipulador de estado correto.
        $handler = $this->state_handler_factory->make($current_state);

        // Delega o processamento da mensagem para o manipulador de estado.
        return $handler->handle($message, $this->session, $analysis);
    }
}
