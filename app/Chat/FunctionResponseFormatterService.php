<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\FunctionResponseFormatterPrompt;
use App\Models\ChatSession;

/**
 * Serviço para "traduzir" respostas de API em linguagem natural.
 */
class FunctionResponseFormatterService
{
    protected AiAdapterInterface $ai_adapter;
    protected FunctionResponseFormatterPrompt $prompt;

    public function __construct(AiAdapterInterface $ai_adapter, FunctionResponseFormatterPrompt $prompt)
    {
        $this->ai_adapter = $ai_adapter;
        $this->prompt = $prompt;
    }

    /**
     * Formata os dados brutos em uma resposta amigável.
     *
     * @param array $data Os dados da API.
     * @param ChatSession $session A sessão de chat.
     * @return string A resposta formatada.
     */
    public function format(array $data, ChatSession $session): string
    {
        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $history_array = $session->getHistoryAsArray();

        $prompt_text = $this->prompt->build($json_data);

        // O adapter de IA precisa ser configurado com o prompt de sistema do "ATENDENTE"
        // para manter a persona correta ao gerar a resposta.
        $system_prompt = app(Prompts\AttendantPrompt::class)->build();

        return $this->ai_adapter->getChat(
            $system_prompt . "\n\n" . $prompt_text,
            $history_array,
            false // Pede TEXTO
        );
    }
}
