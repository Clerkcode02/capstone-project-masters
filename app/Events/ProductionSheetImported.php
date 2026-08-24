<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ProductionSheetImported
{
    use Dispatchable;

    public function __construct(
        public readonly User $importedBy,
        public readonly string $originalFilename,
        public readonly int $inserted,
        public readonly int $skipped,
    ) {}
}
