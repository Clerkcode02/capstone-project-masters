<?php

namespace App\Domain\Administration\Services;

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Models\Setting;

class SettingsService
{
    /** @var array<string, int|float|bool|string> */
    private array $cache = [];

    public function get(string $key): int|float|bool|string
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $setting = Setting::query()->where('key', $key)->firstOrFail();

        return $this->cache[$key] = $this->cast($setting);
    }

    public function complexityWeight(ComplexityTier $tier): int
    {
        return (int) $this->get("complexity_weight_{$tier->value}");
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
