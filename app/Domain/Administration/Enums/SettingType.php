<?php

namespace App\Domain\Administration\Enums;

enum SettingType: string
{
    case Int = 'int';
    case Decimal = 'decimal';
    case Bool = 'bool';
    case String = 'string';
}
