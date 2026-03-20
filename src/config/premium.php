<?php

return [
    /*
     * Master switch — set PREMIUM_ENABLED=false to disable premium globally
     * (all users treated as free, premium button hidden).
     */
    'enabled' => env('PREMIUM_ENABLED', true),

    /*
     * Maximum premium AI generations per user per calendar month.
     */
    'monthly_limit' => (int) env('PREMIUM_MONTHLY_LIMIT', 200),
];
