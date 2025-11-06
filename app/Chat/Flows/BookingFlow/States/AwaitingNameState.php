<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Log; // Importa a classe Log

class AwaitingNameState extends AbstractStateHandler implements StateHandler
{
    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        $session->customer_name = $message;
        $this->updateData($session, ['user_name' => $message]);
        Log::info('[AwaitingNameState] Nome do cliente salvo.', [
            'session_id' => $session->id,
            'customer_name' => $message
        ]);

        $this->updateState($session, BookingState::AWAITING_PHONE);

        return "Obrigado, {$message}. Agora, por favor, me informe seu telefone com DDD.";
    }
}
