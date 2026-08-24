<?php

namespace App\Listeners;

use App\Events\ProductionSheetImported;
use App\Models\AuditLog;

class LogProductionSheetImported
{
    public function handle(ProductionSheetImported $event): void
    {
        AuditLog::create([
            'user_id' => $event->importedBy->id,
            'action' => 'production_sheet_imported',
            'description' => "Imported '{$event->originalFilename}': {$event->inserted} row(s) inserted, {$event->skipped} row(s) skipped.",
        ]);
    }
}
