<?php

namespace App\Adapters\Ai;

interface AiAdapterInterface
{
    /**
     * Obtém uma resposta de chat do provedor de IA.
     *
     * @param string $prompt O prompt principal (mensagem do sistema ou instrução).
     * @param array $history O histórico da conversa (array de arrays associativos).
     * @param bool $expect_json Define se a resposta DEVE ser um JSON.
     * @return string A resposta em texto da IA (seja texto simples ou uma string JSON).
     */
    public function getChat(string $prompt, array $history = [], bool $expect_json = false): string;
}
