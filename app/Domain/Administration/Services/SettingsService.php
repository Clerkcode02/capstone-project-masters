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
        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->load($key);
        }

        return $this->cache[$key];
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function decimal(string $key): float
    {
        return (float) $this->get($key);
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    public function string(string $key): string
    {
        return (string) $this->get($key);
    }

    private function load(string $key): int|float|bool|string
    {
        $setting = Setting::query()->where('key', $key)->first();

        if (! $setting) {
            throw new RuntimeException("Setting [{$key}] is not configured.");
        }

        return match ($setting->type) {
            'int' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            default => $setting->value,
        };
    }
}
