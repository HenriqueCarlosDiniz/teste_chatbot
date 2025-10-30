<?php

namespace App\Adapters\Messaging;

use Illuminate\Http\Request;

/**
 * Interface MessagingAdapterInterface
 *
 * Define o contrato para os adaptadores de plataformas de mensagens.
 * Cada adaptador será responsável por "traduzir" os dados de uma plataforma específica
 * (como WhatsApp, Telegram) para um formato padronizado que nossa aplicação entende,
 * e também por enviar as respostas de volta para a plataforma de origem.
 */
interface MessagingAdapterInterface
{
    /**
     * Processa a requisição de entrada (webhook) e extrai informações relevantes.
     *
     * @param Request $request A requisição HTTP recebida do webhook da plataforma.
     * @return array Deve retornar um array padronizado com os dados da mensagem.
     * Exemplo: ['senderId' => '123456789', 'message' => 'Olá, mundo!']
     */
    public function processIncoming(Request $request): array;

    /**
     * Envia uma mensagem de resposta para o usuário na plataforma de origem.
     *
     * @param string $recipientId O identificador único do destinatário na plataforma.
     * @param string $message A mensagem de texto a ser enviada.
     * @return bool Retorna true em caso de sucesso no envio, false caso contrário.
     */
    public function sendResponse(string $recipientId, string $message): bool;
}
