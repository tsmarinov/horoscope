<?php

if (! function_exists('ui_trans')) {
    /**
     * Gender-aware UI translation helper with fallback chain:
     * 1. If $gender set: try __("ui.{key}_{gender}", [], $locale) — return if translation exists
     * 2. Try __("ui.{key}", [], $locale) — return if found
     * 3. Fallback: __("ui.{key}", [], 'en')
     *
     * @param  string       $key     Dot-notation key inside the 'ui' namespace (e.g. 'retrograde.title')
     * @param  ?string      $gender  Resolved gender: 'male', 'female', or null
     * @param  string|null  $locale  Target locale (null = app locale)
     * @param  array        $replace Replacement parameters
     * @return string
     */
    function ui_trans(string $key, ?string $gender = null, ?string $locale = null, array $replace = []): string
    {
        $locale = $locale ?? app()->getLocale();

        // Step 1: gendered key in target locale
        if ($gender !== null) {
            $genderedKey = "ui.{$key}_{$gender}";
            $result = __($genderedKey, $replace, $locale);
            // __() returns the key itself when translation is missing
            if ($result !== $genderedKey) {
                return $result;
            }
        }

        // Step 2: neutral key in target locale
        $neutralKey = "ui.{$key}";
        $result = __($neutralKey, $replace, $locale);
        if ($result !== $neutralKey) {
            return $result;
        }

        // Step 3: fallback to English neutral
        if ($locale !== 'en') {
            return __($neutralKey, $replace, 'en');
        }

        return $result;
    }
}
