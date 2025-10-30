<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $userId;
    public string $responseMessage;

    /**
     * Cria uma nova instância do evento.
     *
     * @param string $userId
     * @param string $responseMessage
     */
    public function __construct(string $userId, string $responseMessage)
    {
        $this->userId = $userId;
        $this->responseMessage = $responseMessage;
    }

    /**
     * Obtém os canais nos quais o evento deve ser transmitido.
     *
     * Usamos um canal público onde o nome do canal é o próprio ID do usuário (session_id),
     * garantindo que a mensagem só seja entregue para o cliente correto.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->userId);
    }

    /**
     * O nome do evento a ser transmitido.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }
}
