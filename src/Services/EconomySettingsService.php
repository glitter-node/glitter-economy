<?php

namespace Modules\Glitter\Economy\Services;

use App\Contracts\Extension\ModuleSettingsInterface;
use Illuminate\Support\Arr;
use LogicException;

class EconomySettingsService implements ModuleSettingsInterface
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $defaults = null;

    public function getSettingsDefaultsPath(): ?string
    {
        $path = dirname(__DIR__, 2).'/config/settings/defaults.json';

        return file_exists($path) ? $path : null;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->getDefaultValues(), $key, $default);
    }

    public function setSetting(string $key, mixed $value): bool
    {
        throw new LogicException(__('glitter-economy::messages.exceptions.settings_write_deferred'));
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllSettings(): array
    {
        return $this->getDefaultValues();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(string $category): array
    {
        $settings = Arr::get($this->getDefaultValues(), $category, []);

        return is_array($settings) ? $settings : [];
    }

    public function saveSettings(array $settings): bool
    {
        throw new LogicException(__('glitter-economy::messages.exceptions.settings_write_deferred'));
    }

    /**
     * @return array<string, mixed>
     */
    public function getFrontendSettings(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultValues(): array
    {
        $defaults = $this->getDefaults();

        return $defaults['defaults'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaults(): array
    {
        if ($this->defaults !== null) {
            return $this->defaults;
        }

        $path = $this->getSettingsDefaultsPath();
        if ($path === null) {
            return $this->defaults = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return $this->defaults = is_array($decoded) ? $decoded : [];
    }
}
