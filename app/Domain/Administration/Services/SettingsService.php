<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsService
{
    /**
     * @var Collection<string, Setting>|null
     */
    private ?Collection $cache = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->all()->get($key);

        if ($setting === null) {
            return $default;
        }

        return $this->cast($setting);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getDecimal(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    /**
     * @return Collection<string, Setting>
     */
    public function all(): Collection
    {
        return $this->cache ??= Setting::query()->get()->keyBy('key');
    }

    private function cast(Setting $setting): mixed
    {
        return match (SettingType::from($setting->type)) {
            SettingType::Int => (int) $setting->value,
            SettingType::Decimal => (float) $setting->value,
            SettingType::Bool => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::String => $setting->value,
        };
    }
}
