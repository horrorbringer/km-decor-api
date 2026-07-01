<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.color_primary', '#061b73');
        $this->migrator->add('general.color_danger', '#ed1c24');
        $this->migrator->add('general.color_success', '#16a34a');
        $this->migrator->add('general.color_warning', '#c9a84c');
        $this->migrator->add('general.color_info', '#2563eb');
    }
};
