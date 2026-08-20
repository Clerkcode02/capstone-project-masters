<?php

namespace App\Domain\Capacity\Exceptions;

use RuntimeException;

class MissingBaselineException extends RuntimeException
{
    public static function forPeriod(int $year, int $month): self
    {
        return new self(
            "No monthly_baselines row exists for {$year}-{$month}. An administrator must configure H_base for this period before capacity can be calculated."
        );
    }
}
