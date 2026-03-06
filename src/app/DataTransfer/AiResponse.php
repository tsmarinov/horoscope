<?php

namespace App\DataTransfer;

final class AiResponse
{
    public function __construct(
        public readonly string $text,
        public readonly int    $inputTokens,
        public readonly int    $outputTokens,
        public readonly float  $costUsd,
    ) {}
}
