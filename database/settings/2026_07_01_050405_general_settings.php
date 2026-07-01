<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'KM Decor');
        $this->migrator->add('general.site_description', null);
        $this->migrator->add('general.logo_url', null);
        $this->migrator->add('general.favicon_url', null);
        $this->migrator->add('general.contact_email', null);
        $this->migrator->add('general.contact_phone', null);
        $this->migrator->add('general.address', null);
        $this->migrator->add('general.facebook_url', null);
        $this->migrator->add('general.instagram_url', null);
        $this->migrator->add('general.telegram_url', null);
        $this->migrator->add('general.youtube_url', null);
        $this->migrator->add('general.frontend_url', null);
        $this->migrator->add('general.color_primary', '#061b73');
        $this->migrator->add('general.color_danger', '#ed1c24');
        $this->migrator->add('general.color_success', '#16a34a');
        $this->migrator->add('general.color_warning', '#c9a84c');
        $this->migrator->add('general.color_info', '#2563eb');
    }
};
