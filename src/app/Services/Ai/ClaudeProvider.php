<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Contracts\AiProvider;
use App\DataTransfer\AiResponse;

class ClaudeProvider implements AiProvider
{
    // Pricing per million tokens (as of 2026-03)
    private const PRICE_INPUT  = ['claude-sonnet-4-6' => 3.00,  'claude-haiku-4-5-20251001' => 0.80];
    private const PRICE_OUTPUT = ['claude-sonnet-4-6' => 15.00, 'claude-haiku-4-5-20251001' => 4.00];

    private Client $client;
    private string $model;

    public function __construct(Client $client, string $model)
    {
        $this->client = $client;
        $this->model  = $model;
    }

    public function generate(string $prompt, string $system, int $maxTokens = 1024): AiResponse
    {
        $message = $this->client->messages->create(
            maxTokens: $maxTokens,
            messages: [['role' => 'user', 'content' => $prompt]],
            model: $this->model,
            system: $system,
        );

        $in    = $message->usage->inputTokens  ?? 0;
        $out   = $message->usage->outputTokens ?? 0;
        $prIn  = (self::PRICE_INPUT[$this->model]  ?? 3.00) / 1_000_000;
        $prOut = (self::PRICE_OUTPUT[$this->model] ?? 15.00) / 1_000_000;

        return new AiResponse(
            text:         $message->content[0]->text ?? '',
            inputTokens:  $in,
            outputTokens: $out,
            costUsd:      round($in * $prIn + $out * $prOut, 6),
        );
    }
}
