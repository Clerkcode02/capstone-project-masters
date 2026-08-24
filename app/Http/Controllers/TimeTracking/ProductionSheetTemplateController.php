<?php

namespace App\Http\Controllers\TimeTracking;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductionSheetTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $this->authorize('import', TimeLog::class);

        return response()->download(
            base_path('docs/samples/production-sheet-template.csv'),
            'production-sheet-template.csv',
        );
    }
}
