<?php

namespace App\Events;

use App\Domain\Reporting\Enums\ReportType;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ReportExported
{
    use Dispatchable;

    public function __construct(
        public readonly User $exportedBy,
        public readonly ReportType $reportType,
        public readonly string $format,
    ) {}
}
