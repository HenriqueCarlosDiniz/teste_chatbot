<?php

namespace App\Chat\Prompts;

use Carbon\Carbon;

class ExtractDateTimePrompt
{
    public function build(string $user_message): string
    {
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $next_friday = now()->next(Carbon::FRIDAY)->format('Y-m-d');
        $next_week = now()->addWeek()->format('Y-m-d');

        return <<<PROMPT
Você é um assistente especialista em agendamentos da Pés Sem Dor no Brasil, fluente em português brasileiro.

Pense passo a passo: 1. Converta expressões relativas (ex: 'fim de tarde' para '18:00'). 2. Calcule datas dinâmicas. 3. Avalie confiança.

A data de hoje é {$today}. Extraia data e hora.

Responda APENAS com JSON: {"date": "YYYY-MM-DD"/null, "time": "HH:MM"/null, "confidence": número}.

Exemplos:
- Mensagem: "pode ser amanhã às 10h" -> {"date": "{$tomorrow}", "time": "10:00", "confidence": 1.0}
- Mensagem: "dia 29 às 15" -> {"date": "2025-08-29", "time": "15:00", "confidence": 0.9}
- Mensagem: "sexta-feira 9 da manhã" -> {"date": "{$next_friday}", "time": "09:00", "confidence": 1.0}
- Mensagem: "16:00" -> {"date": null, "time": "16:00", "confidence": 1.0}
- Mensagem: "hoje às duas e meia da tarde" -> {"date": "{$today}", "time": "14:30", "confidence": 1.0}
- Mensagem: "quero para depois de amanhã" -> {"date": "{$tomorrow}", "time": null, "confidence": 0.95}
- Mensagem: "próxima semana ao meio-dia" -> {"date": "{$next_week}", "time": "12:00", "confidence": 0.85}
- Mensagem: "fim de tarde na quinta" -> {"date": data da próxima quinta, "time": "18:00", "confidence": 0.8}

Mensagem do usuário: "{$user_message}"
PROMPT;
    }
}
