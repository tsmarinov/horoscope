<?php

namespace App\Enums;

enum ReportMode: string
{
    case Organic    = 'organic';
    case Simplified = 'simplified';
    case AiL1       = 'ai_l1';
    case AiL1Haiku  = 'ai_l1_haiku';

    public function isAi(): bool
    {
        return $this === self::AiL1 || $this === self::AiL1Haiku;
    }
}
