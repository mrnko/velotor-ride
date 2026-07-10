<?php

namespace App\Services\Parsing;

final class ParsedResult
{
    private function __construct(
        public readonly bool $matched,
        public readonly bool $valid,
        public readonly ?float $distanceKm,
        public readonly ?string $invalidReason = null,
    ) {}

    public static function notMatched(): self
    {
        return new self(matched: false, valid: false, distanceKm: null);
    }

    public static function invalid(string $reason): self
    {
        return new self(matched: true, valid: false, distanceKm: null, invalidReason: $reason);
    }

    public static function valid(float $distanceKm): self
    {
        return new self(matched: true, valid: true, distanceKm: $distanceKm);
    }
}
