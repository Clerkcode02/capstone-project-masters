<?php

namespace App\Domain\Reporting\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $auditLogs) {}

    public function collection(): Collection
    {
        return $this->auditLogs;
    }

    public function headings(): array
    {
        return ['Date/Time', 'User', 'Action', 'Auditable', 'Description', 'IP Address'];
    }

    public function map($auditLog): array
    {
        /** @var AuditLog $auditLog */
        return [
            $auditLog->created_at->format('Y-m-d H:i:s'),
            $auditLog->user?->full_name ?? 'System',
            $auditLog->action,
            $auditLog->auditable_type ? class_basename($auditLog->auditable_type).' #'.$auditLog->auditable_id : '—',
            $auditLog->description,
            $auditLog->ip_address,
        ];
    }
}
