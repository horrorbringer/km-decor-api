<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public ?string $site_description;

    public ?string $logo_url;

    public ?string $favicon_url;

    public ?string $contact_email;

    public ?string $contact_phone;

    public ?string $address;

    public ?string $facebook_url;

    public ?string $instagram_url;

    public ?string $telegram_url;

    public ?string $youtube_url;

    public ?string $frontend_url;

    public string $color_primary = '#061b73';

    public string $color_danger = '#ed1c24';

    public string $color_success = '#16a34a';

    public string $color_warning = '#c9a84c';

    public string $color_info = '#2563eb';

    public static function group(): string
    {
        return 'general';
    }
}
