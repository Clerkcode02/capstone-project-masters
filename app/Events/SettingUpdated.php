<?php

namespace App\Events;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class SettingUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly Setting $setting,
        public readonly ?User $actor,
        public readonly string $oldValue,
        public readonly string $newValue,
    ) {}
}
