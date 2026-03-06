<?php

namespace App\Contracts;

interface AiProvider
{
    /**
     * Generate text using the AI provider.
     *
     * @param  string $prompt     User/human turn content
     * @param  string $system     System prompt
     * @param  int    $maxTokens  Maximum tokens to generate
     * @return \App\DataTransfer\AiResponse
     */
    public function generate(string $prompt, string $system, int $maxTokens = 1024): \App\DataTransfer\AiResponse;
}
