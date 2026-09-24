<?php

namespace App\Notifications;

use App\Domain\Reporting\Enums\ReportType;
use Illuminate\Notifications\Notification;

class ReportReady extends Notification
{
    public function __construct(
        public readonly ReportType $reportType,
        public readonly string $storagePath,
        public readonly string $filename,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_type' => $this->reportType->value,
            'label' => $this->reportType->label(),
            'storage_path' => $this->storagePath,
            'filename' => $this->filename,
        ];
    }
}
