<?php

namespace App\Chat;

use App\Adapters\Ai\AiAdapterInterface;
use App\Chat\Prompts\IsAffirmativePrompt;
use App\Chat\Prompts\IsNegativePrompt;
use App\Chat\Prompts\IsQuestionPrompt;
use Illuminate\Support\Facades\Log;

/**
 * Analisa respostas curtas do utilizador para determinar a sua natureza (afirmativa, negativa, etc.).
 */
class ResponseAnalyzer
{
    protected AiAdapterInterface $ai;
    protected IsAffirmativePrompt $isAffirmativePrompt;
    protected IsNegativePrompt $isNegativePrompt;
    protected IsQuestionPrompt $isQuestionPrompt;

    public function __construct(
        AiAdapterInterface $ai,
        IsAffirmativePrompt $isAffirmativePrompt,
        IsNegativePrompt $isNegativePrompt,
        IsQuestionPrompt $isQuestionPrompt
    ) {
        $this->ai = $ai;
        $this->isAffirmativePrompt = $isAffirmativePrompt;
        $this->isNegativePrompt = $isNegativePrompt;
        $this->isQuestionPrompt = $isQuestionPrompt;
    }

    /**
     * Verifica se a mensagem é uma afirmação.
     *
     * @param string $message
     * @return bool
     */
    public function isAffirmative(string $message): bool
    {
        $prompt = $this->isAffirmativePrompt->build($message);
        $response = $this->ai->getChat($prompt, 'analyzer_affirmative');
        Log::info('[ResponseAnalyzer] Verificação afirmativa.', ['message' => $message, 'response' => $response]);
        return str_contains(strtolower($response), 'sim');
    }

    /**
     * Verifica se a mensagem é uma negação.
     *
     * @param string $message
     * @return bool
     */
    public function isNegative(string $message): bool
    {
        $prompt = $this->isNegativePrompt->build($message);
        $response = $this->ai->getChat($prompt, 'analyzer_negative');
        Log::info('[ResponseAnalyzer] Verificação negativa.', ['message' => $message, 'response' => $response]);
        return str_contains(strtolower($response), 'sim');
    }

    /**
     * Verifica se a mensagem é uma pergunta.
     *
     * @param string $message
     * @return bool
     */
    public function isAQuestion(string $message): bool
    {
        $prompt = $this->isQuestionPrompt->build($message);
        $response = $this->ai->getChat($prompt, 'analyzer_question');
        Log::info('[ResponseAnalyzer] Verificação de pergunta.', ['message' => $message, 'response' => $response]);
        return str_contains(strtolower($response), 'sim');
    }
}
