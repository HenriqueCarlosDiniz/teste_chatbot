<?php

namespace App\Chat\Flows\BookingFlow;

use App\Chat\Enums\BookingState;
use App\Chat\Flows\BookingFlow\Contracts\StateHandler;
use App\Chat\Flows\BookingFlow\States\AwaitingConfirmationState;
use App\Chat\Flows\BookingFlow\States\AwaitingCorrectionState;
use App\Chat\Flows\BookingFlow\States\AwaitingDateTimeState;
use App\Chat\Flows\BookingFlow\States\AwaitingLocationChoiceState;
use App\Chat\Flows\BookingFlow\States\AwaitingLocationState;
use App\Chat\Flows\BookingFlow\States\AwaitingNameState;
use App\Chat\Flows\BookingFlow\States\AwaitingPhoneState;
use App\Chat\Flows\BookingFlow\States\InitialState;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Class StateHandlerFactory
 * Responsável por criar a instância correta do manipulador de estado com base no estado atual.
 */
class StateHandlerFactory
{
    protected Container $container;

    protected array $state_map = [
        BookingState::INITIAL => InitialState::class,
        BookingState::AWAITING_LOCATION => AwaitingLocationState::class,
        BookingState::AWAITING_LOCATION_CHOICE => AwaitingLocationChoiceState::class,
        BookingState::AWAITING_DATE_TIME => AwaitingDateTimeState::class,
        BookingState::AWAITING_NAME => AwaitingNameState::class,
        BookingState::AWAITING_PHONE => AwaitingPhoneState::class,
        BookingState::AWAITING_CONFIRMATION => AwaitingConfirmationState::class,
        BookingState::AWAITING_CORRECTION => AwaitingCorrectionState::class,
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Cria e retorna a instância do manipulador para o estado fornecido.
     *
     * @param string $state O estado atual do fluxo.
     * @return StateHandler
     */
    public function make(string $state): StateHandler
    {
        if (!isset($this->state_map[$state])) {
            throw new InvalidArgumentException("Nenhum manipulador de estado encontrado para o estado: {$state}");
        }

        $class = $this->state_map[$state];
        return $this->container->make($class);
    }
}
