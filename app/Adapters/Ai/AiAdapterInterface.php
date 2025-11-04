<?php

namespace App\Adapters\Ai;

/**
 * Interface AiAdapterInterface
 *
 * Define o contrato que todos os adaptadores de serviços de IA devem seguir.
 */
interface AiAdapterInterface
{
    /**
     * Envia um prompt para a IA e retorna a resposta.
     *
     * @param string $prompt O prompt completo a ser enviado para a IA.
     * @param string $sessionId Um identificador único para a sessão, para manter o contexto se necessário.
     * @param array $context O histórico da conversa (opcional).
     * @return string A resposta gerada pela IA.
     */
    public function getChat(string $prompt, string $sessionId, array $context = []): string;
}
