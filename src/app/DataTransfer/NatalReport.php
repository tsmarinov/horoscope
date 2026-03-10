<?php

namespace App\DataTransfer;

use App\Enums\ReportMode;
use App\Models\NatalChart;

readonly class NatalReport
{
    /**
     * @param  NatalReportSection[]  $sections
     */
    public function __construct(
        public NatalChart  $chart,
        public array       $sections,
        public ReportMode  $mode,
        public string      $language,
        /** AI-generated introduction (L1/L1 Haiku only) */
        public ?string     $introduction  = null,
        /** AI-generated house lords section (L1 only) */
        public ?string     $houseLords    = null,
        /** AI-generated conclusion */
        public ?string     $conclusion    = null,
        /** Token usage — passed through to ai_texts on persist */
        public int         $aiTokensIn    = 0,
        public int         $aiTokensOut   = 0,
        public float       $aiCostUsd     = 0.0,
    ) {}
}
