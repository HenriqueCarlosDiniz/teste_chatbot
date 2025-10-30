<?php

namespace App\Chat\Flows\BookingFlow\States;

use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Data\ConversationAnalysisDTO;
use App\Models\ChatSession;
use App\Services\SchedulingService;
use App\Services\GoogleMapsService;
use App\Chat\PromptManager;
use Illuminate\Support\Facades\Log;

class AwaitingLocationState extends AbstractStateHandler implements StateHandler
{
    protected SchedulingService $scheduling_service;
    protected GoogleMapsService $google_maps_service;
    protected PromptManager $prompt_manager;

    public function __construct(
        SchedulingService $scheduling_service,
        GoogleMapsService $google_maps_service,
        PromptManager $prompt_manager
    ) {
        $this->scheduling_service = $scheduling_service;
        $this->google_maps_service = $google_maps_service;
        $this->prompt_manager = $prompt_manager;
    }

    public function handle(string $message, ChatSession $session, ?ConversationAnalysisDTO $analysis): string
    {
        $entities = $this->prompt_manager->extractEntities($message, $session->id, $session->getFormattedHistory());

        if (!$entities || !$entities->location_type || !$entities->location_value) {
            return "Não consegui entender a localização. Por favor, envie o nome de um estado, cidade ou CEP.";
        }

        return $this->processLocationSearch($session, $entities->location_type, $entities->location_value);
    }

    private function processLocationSearch(ChatSession $session, string $location_type, string $location_value): string
    {
        Log::info('[AwaitingLocationState] Iniciando busca de localização.', ['type' => $location_type, 'value' => $location_value]);
        $context = '';

        if ($location_type === 'cep') {
            $coordinates = $this->google_maps_service->getCoordinatesFromCep($location_value);
            if (!$coordinates) {
                return "Não consegui encontrar a localização para o CEP informado. Por favor, tente novamente ou informe uma cidade.";
            }

            $nearest_unit = $this->scheduling_service->obterUnidadeMaisProxima($coordinates['latitude'], $coordinates['longitude']);

            if (!$nearest_unit) {
                return "Infelizmente, não encontrei unidades próximas a este CEP. Gostaria de tentar buscar por cidade e estado?";
            }

            $context = "mais próxima do CEP {$location_value}";
            return $this->formatUnitList($session, [$nearest_unit], $context);
        }

        $all_units = $this->scheduling_service->obterTodasAsUnidadesAtivas();
        $filtered_units = [];

        if ($location_type === 'state') {
            $filtered_units = $this->scheduling_service->filtrarUnidades($all_units, estado: $location_value);
            $context = "no estado de {$location_value}";
            $this->updateData($session, ['criterios_atuais' => ['estado' => $location_value]]);
        } else { // city or neighborhood
            $filtered_units = $this->scheduling_service->filtrarUnidades($all_units, cidade: $location_value);
            $context = "na cidade de {$location_value}";
            $this->updateData($session, ['criterios_atuais' => ['cidade' => $location_value]]);
        }

        Log::info('[AwaitingLocationState] Unidades filtradas.', ['count' => count($filtered_units), 'contexto' => $context]);
        return $this->formatUnitList($session, $filtered_units, $context);
    }
}
