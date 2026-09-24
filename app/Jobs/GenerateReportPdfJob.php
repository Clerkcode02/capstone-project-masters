<?php

namespace App\Jobs;

use App\Domain\Reporting\Enums\ReportType;
use App\Domain\Reporting\Services\ReportPdfService;
use App\Events\ReportExported;
use App\Models\User;
use App\Notifications\ReportReady;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public readonly int $requestedByUserId,
        public readonly ReportType $reportType,
        public readonly array $params,
    ) {}

    public function handle(ReportPdfService $service): void
    {
        $actor = User::findOrFail($this->requestedByUserId);

        ['view' => $view, 'data' => $data, 'filename' => $filename] = $service->build($this->reportType, $this->params, $actor);

        $pdf = Pdf::loadView($view, $data);

        $path = "reports/{$actor->id}/".Str::uuid().'-'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        $actor->notify(new ReportReady($this->reportType, $path, $filename));

        ReportExported::dispatch($actor, $this->reportType, 'pdf');
    }
}
