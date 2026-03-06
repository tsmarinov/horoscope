<?php

namespace App\DataTransfer;

readonly class NatalReportSection
{
    public function __construct(
        /** Block key, e.g. "sun_trine_moon", "ascendant_in_scorpio" */
        public string  $key,
        /** Section label, e.g. "natal", "natal_synthesis" */
        public string  $section,
        /** Human-readable title for this section */
        public string  $title,
        /** HTML-formatted text content */
        public string  $text,
        /** positive | negative | neutral */
        public string  $tone      = 'neutral',
        /** AI-generated transitional paragraph (L1/L2 only) */
        public ?string $transition  = null,
        /** TextBlock ID for organic/simplified/L1 modes — stored for cache hydration */
        public ?int    $textBlockId = null,
    ) {}
}
