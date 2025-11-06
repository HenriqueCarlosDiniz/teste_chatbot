<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use App\Chat\PromptManager;
use Illuminate\Support\Facades\Log;

class InitialState extends AbstractStateHandler implements StateHandler
{
    protected SchedulingService $scheduling_service;
    protected PromptManager $prompt_manager;

    public function __construct(SchedulingService $scheduling_service, PromptManager $prompt_manager)
    {
        $this->scheduling_service = $scheduling_service;
        $this->prompt_manager = $prompt_manager;
    }

    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        $location_data = $this->prompt_manager->extractLocation($message, $session->id);

        if ($location_data && $location_data->type !== 'unknown' && $location_data->value) {
            // Se sim, já podemos pular para o estado AWAITING_LOCATION para processar.
            $this->updateState($session, BookingState::AWAITING_LOCATION);
            $awaitingLocationState = app(AwaitingLocationState::class);
            return $awaitingLocationState->handle($message, $session, $analysis);
        }

        $entities = $this->prompt_manager->extractEntities($message, $session->id, $session->getFormattedHistory());

        if ($entities && $entities->location_type && $entities->location_value) {
            // Se sim, já podemos pular para o estado AWAITING_LOCATION para processar.
            $this->updateState($session, BookingState::AWAITING_LOCATION);
            $awaitingLocationState = app(AwaitingLocationState::class);
            return $awaitingLocationState->handle($message, $session, $analysis);
        }

        // Se não, mudamos o estado para aguardar a localização e pedimos ao usuário.
        $this->updateState($session, BookingState::AWAITING_LOCATION);
        return "Para começarmos, por favor, me informe o estado, a cidade ou o CEP onde deseja o atendimento.";
    }
}
