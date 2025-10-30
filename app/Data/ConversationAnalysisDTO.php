<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\In;

class ConversationAnalysisDTO extends Data
{
    public function __construct(
        #[In(['agendamento', 'consultar_agendamento', 'cancelamento', 'reagendamento', 'saudacao', 'informacao_geral', 'desconhecida'])]
        public string $intent,
        public float $confidence,
    ) {}
}
