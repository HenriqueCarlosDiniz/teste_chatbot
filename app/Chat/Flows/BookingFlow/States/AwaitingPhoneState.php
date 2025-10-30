<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;

class AwaitingPhoneState extends AbstractStateHandler implements StateHandler
{
    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        $cleaned_phone = preg_replace('/\D/', '', $message);
        if (strlen($cleaned_phone) < 10 || strlen($cleaned_phone) > 11) {
            return "O número de telefone parece inválido. Por favor, envie novamente com o DDD.";
        }
        $this->updateData($session, [
            'user_ddd' => substr($cleaned_phone, 0, 2),
            'user_phone' => substr($cleaned_phone, 2),
            'full_phone' => $cleaned_phone
        ]);
        $this->updateState($session, BookingState::AWAITING_CONFIRMATION);
        return $this->getConfirmationText($session);
    }
}
