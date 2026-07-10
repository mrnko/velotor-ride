<?php

namespace App\Services\Parsing;

use App\Models\Setting;

class ResultParser
{
    /** Matches "км", "km", "километров"/"километр", "кілометр"/"кілометрів" in any inflected form. */
    private const UNIT_PATTERN = '(?:км\.?|kilometers?|kilometres?|km|километ\p{L}*|кілометр\p{L}*)';

    /**
     * Parses one chat message. Returns a not-matched result for anything
     * that doesn't look like a ride result at all (so the bot can silently
     * ignore normal chat), otherwise returns matched + valid/invalid.
     */
    public function parse(string $text): ParsedResult
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text));

        $keywordPattern = '/^(?:результат|result)\s*[:\-]?\s*\+?\s*(\d+(?:[.,]\d+)?)\s*'.self::UNIT_PATTERN.'?\s*[.!]*$/iu';
        $plusLedPattern = '/^\+\s*(\d+(?:[.,]\d+)?)\s*'.self::UNIT_PATTERN.'\s*[.!]*$/iu';

        if (preg_match($keywordPattern, $normalized, $matches) || preg_match($plusLedPattern, $normalized, $matches)) {
            $distance = (float) str_replace(',', '.', $matches[1]);

            return $this->validate($distance);
        }

        return ParsedResult::notMatched();
    }

    private function validate(float $distance): ParsedResult
    {
        $max = (float) Setting::get('max_distance_km', config('velotor.max_distance_km', 1000));

        if ($distance <= 0) {
            return ParsedResult::invalid('too_low');
        }

        if ($distance > $max) {
            return ParsedResult::invalid('too_high');
        }

        return ParsedResult::valid($distance);
    }
}
