<?php

namespace App\Jobs;

use App\Chat\ChatApplicationManager;
use App\Chat\SentimentAnalyzerService;
use App\Events\ChatMessageSent;
use App\Models\ChatSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    protected string $message;
    protected string $session_id;
    protected string $channel;
    protected ?string $phone_number;

    public function __construct(string $message, string $session_id, string $channel, ?string $phone_number = null)
    {
        $this->message = $message;
        $this->session_id = $session_id;
        $this->channel = $channel;
        $this->phone_number = $phone_number;
    }

    public function handle(ChatApplicationManager $chat_manager, SentimentAnalyzerService $sentiment_analyzer): void
    {
        Log::info('[INÍCIO] Iniciando job ProcessChatMessage.', [
            'session_id' => $this->session_id,
            'phone_number' => $this->phone_number,
            'channel' => $this->channel,
            'message' => $this->message,
        ]);

        try {
            $session = ChatSession::firstOrCreate(
                ['id' => $this->session_id],
                ['phone_number' => $this->phone_number]
            );

            if ($this->phone_number && !$session->phone_number) {
                $session->phone_number = $this->phone_number;
                $session->save();
            }
            Log::info('[PASSO 1/4] Sessão de chat carregada.', ['session_id' => $session->id]);

            $session->addToHistory('user', $this->message);

            $response_message = $chat_manager->handle($session, $this->message, $this->channel);
            Log::info('[PASSO 2/4] Resposta do ChatApplicationManager recebida.', [
                'response' => $response_message ?? 'NULA'
            ]);

            if ($response_message) {
                $session->addToHistory('bot', $response_message);

                $sentiment = $sentiment_analyzer->analyze($this->message, $this->session_id);
                if ($sentiment) {
                    $session->last_sentiment = $sentiment;
                }

                $state = $session->state ?? [];
                $state['last_bot_message'] = $response_message;
                $session->state = $state;
                $session->save();

                Log::info('[PASSO 3/4] Despachando evento para o Reverb...');
                ChatMessageSent::dispatch($this->session_id, $response_message);
                Log::info('[PASSO 4/4] Evento ChatMessageSent despachado.', ['session_id' => $this->session_id]);
            } else {
                Log::warning('[PASSO 3/4] Resposta do chat está vazia. Nenhum evento despachado.');
            }

            Log::info('[FIM] Job ProcessChatMessage concluído com sucesso.', ['session_id' => $session->id]);
        } catch (Throwable $exception) {
            $this->failed($exception);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('[FALHA] Falha crítica no job ProcessChatMessage', [
            'session_id' => $this->session_id,
            'phone_number' => $this->phone_number,
            'exception_message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
