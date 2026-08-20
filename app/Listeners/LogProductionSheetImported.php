<?php

namespace App\Listeners;

use App\Events\ProductionSheetImported;
use App\Models\AuditLog;

class LogProductionSheetImported
{
    public function handle(ProductionSheetImported $event): void
    {
        AuditLog::create([
            'user_id' => $event->importedByUserId,
            'action' => 'production_sheet_imported',
            'description' => "Imported {$event->rowsImported} time log row(s) from production sheet ({$event->storedPath}).",
        ]);
    }
}
