<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ProductionSheetImported
{
    use Dispatchable;

    public function __construct(
        public readonly int $importedByUserId,
        public readonly string $storedPath,
        public readonly int $rowsImported,
    ) {}
}
