<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa uma sessão de conversa do chatbot.
 */
class ChatSession extends Model
{
    use HasFactory, HasUuids;

    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'chat_sessions';

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'phone_number',
        'state',
        'history',
        'last_sentiment',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'state' => 'array',
        'history' => 'array',
    ];

    /**
     * Obtém o histórico formatado como string para prompts.
     *
     * @return string
     */
    public function getFormattedHistory(): string
    {
        $formatted = '';
        foreach ($this->history ?? [] as $entry) {
            // A "role" do bot deve ser "assistant" para a API da OpenAI
            $role_name = ($entry['role'] === 'bot' || $entry['role'] === 'assistant') ? 'assistant' : 'user';
            $formatted .= ucfirst($role_name) . ": " . $entry['content'] . "\n";
        }
        return $formatted;
    }


    /**
     *
     * @return array
     */
    public function getHistoryAsArray(): array
    {
        $history = $this->history ?? [];

        // Garante que o formato é o esperado pela API (role/content)
        // e que o 'bot' é mapeado para 'assistant'.
        return array_map(function ($entry) {
            return [
                'role' => ($entry['role'] === 'bot' || $entry['role'] === 'assistant') ? 'assistant' : 'user',
                'content' => $entry['content']
            ];
        }, array_slice($history, -5)); // Limita o histórico passado para a IA
    }

    /**
     * Adiciona uma entrada ao histórico e limita às últimas 5 para otimização.
     *
     * @param string $role
     * @param string $content
     * @return void
     */
    public function addToHistory(string $role, string $content): void
    {
        $history = $this->history ?? [];
        // Garante que o role 'bot' seja sempre 'assistant' no histórico
        $history[] = ['role' => ($role === 'bot' ? 'assistant' : $role), 'content' => $content];
        $this->history = array_slice($history, -5);
        $this->save();
    }
}
