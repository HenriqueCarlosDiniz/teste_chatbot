<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Chat\PromptManager;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;

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

        if ($date_time_data === null) {
            return "Não consegui entender o dia e o horário. Por favor, tente informar novamente (ex: 'amanhã às 15h').";
        }

        $this->updateData($session, ['chosen_date' => $date_time_data['date'], 'chosen_time' => $date_time_data['time']]);
        $this->updateState($session, BookingState::AWAITING_NAME);
        return "Entendido. Para continuar, por favor, me informe seu nome completo.";
    }
}
