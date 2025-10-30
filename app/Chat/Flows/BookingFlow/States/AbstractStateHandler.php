<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Enums\BookingState;
use App\Models\ChatSession;
use Carbon\Carbon;

/**
 * Class AbstractStateHandler
 * Contém a lógica compartilhada entre os diferentes manipuladores de estado.
 */
abstract class AbstractStateHandler
{
    /**
     * Atualiza o estado do fluxo na sessão.
     */
    protected function updateState(ChatSession $session, string $new_state): void
    {
        $state = $session->state ?? [];
        $state['flow_state'] = $new_state;
        $session->state = $state;
        $session->save();
    }

    /**
     * Atualiza os dados armazenados na sessão.
     */
    protected function updateData(ChatSession $session, array $data): void
    {
        $state = $session->state ?? [];
        $state['data'] = array_merge($state['data'] ?? [], $data);
        $session->state = $state;
        $session->save();
    }

    /**
     * Limpa o estado da conversa na sessão.
     */
    protected function clearConversationState(ChatSession $session): void
    {
        $session->state = null;
        $session->save();
    }

    /**
     * Formata a lista de unidades para exibição ao usuário.
     */
    protected function formatUnitList(ChatSession $session, array $units, string $context): string
    {
        if (empty($units)) {
            return "Não encontrei unidades disponíveis {$context}.";
        }

        $this->updateData($session, ['unidades_filtradas' => array_values($units)]);
        $this->updateState($session, BookingState::AWAITING_LOCATION_CHOICE);

        $response_text = "Encontrei estas unidades {$context}:\n\n";
        foreach (array_values($units) as $index => $unit) {
            $response_text .= ($index + 1) . ". *{$unit['nomeFranquia']}* - {$unit['bairroFranquia']}, {$unit['cidadeFranquia']}\n";
        }
        $response_text .= "\nQual destas unidades você prefere? Você também pode informar um bairro para refinar a busca.";
        return $response_text;
    }

    /**
     * Formata a lista de horários disponíveis.
     */
    protected function formatAvailableSlots(array $slots, string $unit_name): string
    {
        $response_text = "Para a unidade *{$unit_name}*, encontrei os seguintes horários:\n\n";
        $found_slots = false;
        foreach ($slots as $date => $times) {
            if (!empty($times)) {
                $found_slots = true;
                $formatted_date = Carbon::parse($date)->translatedFormat('l, d \d\e F');
                $response_text .= "*{$formatted_date}:*\n" . implode(' - ', array_map(fn($t) => substr($t, 0, 5), array_keys($times))) . "\n\n";
            }
        }
        return $found_slots ? $response_text . "Qual dia e horário você prefere?" : "Não encontrei horários disponíveis para *{$unit_name}*.";
    }

    /**
     * Gera o texto de confirmação do agendamento.
     */
    protected function getConfirmationText(ChatSession $session): string
    {
        $data = $session->state['data'];
        $date = Carbon::parse($data['chosen_date'])->translatedFormat('d/m/Y');
        return "Vamos confirmar os dados?\n\n*Unidade:* {$data['chosen_unit']['nomeFranquia']}\n*Data:* {$date}\n*Horário:* {$data['chosen_time']}\n*Nome:* {$data['user_name']}\n*Telefone:* {$data['full_phone']}\n\nEstá tudo correto? (Sim/Não)";
    }
}
