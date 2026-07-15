<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ([
            'general.color_primary' => '#061b73',
            'general.color_danger' => '#ed1c24',
            'general.color_success' => '#16a34a',
            'general.color_warning' => '#c9a84c',
            'general.color_info' => '#2563eb',
        ] as $setting => $value) {
            if (! $this->migrator->exists($setting)) {
                $this->migrator->add($setting, $value);
            }
        }
    }
};
