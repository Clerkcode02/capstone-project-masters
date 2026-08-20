<?php

namespace App\Domain\Administration\Services;

use App\Models\Setting;
use RuntimeException;

class SettingsService
{
    /** @var array<string, string>|null */
    private ?array $values = null;

    public function get(string $key): string
    {
        return $this->all()[$key]
            ?? throw new RuntimeException("Setting [{$key}] is not configured.");
    }

    public function getInt(string $key): int
    {
        return (int) $this->get($key);
    }

    public function getFloat(string $key): float
    {
        return (float) $this->get($key);
    }

    public function getBool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, string> */
    private function all(): array
    {
        return $this->values ??= Setting::query()->pluck('value', 'key')->all();
    }
}
