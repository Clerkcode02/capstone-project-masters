<?php

namespace App\Domain\Administration\Services;

use App\Models\Setting;
use RuntimeException;

class SettingsService
{
    /** @var array<string, int|float|bool|string> */
    private array $cache = [];

    public function get(string $key): int|float|bool|string
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            throw new RuntimeException("Setting [{$key}] is not configured.");
        }

        return $this->cache[$key] = $this->cast($setting->value, $setting->type);
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
        return (bool) $this->get($key);
    }

    private function cast(string $value, string $type): int|float|bool|string
    {
        return match ($type) {
            'int' => (int) $value,
            'decimal' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
