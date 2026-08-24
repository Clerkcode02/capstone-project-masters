<?php

namespace App\Domain\TimeTracking\DTOs;

final class ProductionSheetRowResult
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<int, string>  $reasons
     * @param  array<string, mixed>|null  $attributes
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly array $raw,
        public readonly bool $isValid,
        public readonly array $reasons = [],
        public readonly ?array $attributes = null,
    ) {}
}
