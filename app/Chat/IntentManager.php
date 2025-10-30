<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\ExistingAppointmentIntentPrompt;
use App\Chat\Prompts\GeneralIntentPrompt;
use App\Chat\Prompts\ScopeCheckPrompt;
use Illuminate\Support\Str;

/**
 * Class IntentManager
 *
 * Responsável por analisar a mensagem do usuário e classificar sua intenção principal
 * utilizando o serviço de Inteligência Artificial configurado.
 */
class IntentManager
{
    protected AiAdapterInterface $ai_adapter;
    protected ExistingAppointmentIntentPrompt $existing_appointment_prompt;
    protected GeneralIntentPrompt $general_intent_prompt;
    protected ScopeCheckPrompt $scope_check_prompt;

    /**
     * Construtor da classe.
     */
    public function __construct(
        AiAdapterInterface $ai_adapter,
        ExistingAppointmentIntentPrompt $existing_appointment_prompt,
        GeneralIntentPrompt $general_intent_prompt,
        ScopeCheckPrompt $scope_check_prompt
    ) {
        $this->ai_adapter = $ai_adapter;
        $this->existing_appointment_prompt = $existing_appointment_prompt;
        $this->general_intent_prompt = $general_intent_prompt;
        $this->scope_check_prompt = $scope_check_prompt;
    }

    // MODIFICADO: Adicionado $sessionId para cumprir o contrato da interface
    public function getExistingAppointmentIntent(string $user_message, string $sessionId): string
    {
        $prompt = $this->existing_appointment_prompt->build($user_message);
        // MODIFICADO: Utiliza o método getChat
        return $this->normalizeIntent($this->ai_adapter->getChat($prompt, $sessionId));
    }

    // MODIFICADO: Adicionado $sessionId para cumprir o contrato da interface
    public function getIntent(string $user_message, string $sessionId): string
    {
        $prompt = $this->general_intent_prompt->build($user_message);
        // MODIFICADO: Utiliza o método getChat
        $intent = $this->ai_adapter->getChat($prompt, $sessionId);
        return $this->normalizeIntent($intent);
    }

    /**
     * Verifica se a mensagem do usuário está dentro do escopo de atendimento.
     *
     * @param string $user_message A mensagem do usuário.
     * @param string $sessionId O ID da sessão.
     * @return bool Retorna true se a mensagem for relevante, false caso contrário.
     */
    // MODIFICADO: Adicionado $sessionId para cumprir o contrato da interface
    public function isMessageOnTopic(string $user_message, string $sessionId): bool
    {
        if (in_array(strtolower(trim($user_message)), ['oi', 'ola', 'olá', 'bom dia', 'boa tarde', 'boa noite'])) {
            return true;
        }

        $prompt = $this->scope_check_prompt->build($user_message);
        // MODIFICADO: Utiliza o método getChat
        $response = $this->ai_adapter->getChat($prompt, $sessionId);
        return strtolower(trim($response)) === 'yes';
    }

    private function normalizeIntent(string $rawIntent): string
    {
        return Str::lower(trim($rawIntent, " \n\r\t\v\0."));
    }
}
