<?php

namespace App\Domain\Administration\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsService
{
    private ?Collection $cache = null;

    public function get(string $key): int|float|bool|string
    {
        return $this->all()->get($key) ?? throw new \RuntimeException("Unknown setting key [{$key}].");
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function float(string $key): float
    {
        return (float) $this->get($key);
    }

    private function all(): Collection
    {
        return $this->cache ??= Setting::query()->get()->mapWithKeys(
            fn (Setting $setting) => [$setting->key => $this->cast($setting)]
        );
    }

    private function cast(Setting $setting): int|float|bool|string
    {
        return match ($setting->type) {
            'int' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            default => $setting->value,
        };
    }
}
