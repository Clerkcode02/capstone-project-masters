<?php

namespace App\Domain\TimeTracking\Enums;

enum EntryMethod: string
{
    case Timer = 'timer';
    case Manual = 'manual';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Timer => 'Timer',
            self::Manual => 'Manual',
            self::Import => 'Import',
        };
    }
}
