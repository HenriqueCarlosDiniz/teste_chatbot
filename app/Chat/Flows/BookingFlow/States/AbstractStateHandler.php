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

        // Se updateData não for chamado, precisamos salvar aqui.
        // Para garantir, chamamos save() independentemente.
        $session->save();
    }

    /**
     * Atualiza os dados armazenados na sessão.
     * Este método agora também salva quaisquer alterações
     * feitas diretamente no objeto $session (ex: $session->customer_name).
     */
    protected function updateData(ChatSession $session, array $data): void
    {
        $state = $session->state ?? [];
        $state['data'] = array_merge($state['data'] ?? [], $data);
        $session->state = $state;
        $session->save();
    }

    /**
     * Limpa o estado da conversa na sessão, incluindo as colunas dedicadas.
     */
    protected function clearConversationState(ChatSession $session): void
    {
        $session->state = null;
        $session->customer_name = null;
        $session->customer_phone = null;
        $session->selected_unit_id = null;
        $session->selected_datetime = null;
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
     * ATUALIZADO: Agora lê das colunas dedicadas da sessão,
     * com fallback para o JSON state_data.
     */
    protected function getConfirmationText(ChatSession $session): string
    {
        $data = $session->state['data'] ?? [];

        // Lê das novas colunas com fallback para o JSON
        $nome = $session->customer_name ?? $data['user_name'] ?? 'Não informado';
        $telefone = $session->customer_phone ?? $data['full_phone'] ?? 'Não informado';

        // A 'chosen_unit' (com o nome) ainda virá do state_data
        $unidade_nome = $data['chosen_unit']['nomeFranquia'] ?? 'Não informada';

        // Formata a data/hora da nova coluna
        if ($session->selected_datetime) {
            try {
                $datetime = Carbon::parse($session->selected_datetime);
                $data_formatada = $datetime->translatedFormat('d/m/Y');
                $hora_formatada = $datetime->format('H:i');
            } catch (\Exception $e) {
                // Fallback em caso de data inválida
                $data_formatada = $data['chosen_date'] ?? 'Não informada';
                $hora_formatada = $data['chosen_time'] ?? 'Não informada';
            }
        } else {
            // Fallback para o JSON se a coluna for nula
            $data_formatada = isset($data['chosen_date']) ? Carbon::parse($data['chosen_date'])->translatedFormat('d/m/Y') : 'Não informada';
            $hora_formatada = $data['chosen_time'] ?? 'Não informada';
        }

        return "Vamos confirmar os dados?\n\n" .
            "*Unidade:* {$unidade_nome}\n" .
            "*Data:* {$data_formatada}\n" .
            "*Horário:* {$hora_formatada}\n" .
            "*Nome:* {$nome}\n" .
            "*Telefone:* {$telefone}\n\n" .
            "Está tudo correto? (Sim/Não)";
    }
}