<?php

namespace App\Domain\Identity\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }
}
