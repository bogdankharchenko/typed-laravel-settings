<?php

namespace BogdanKharchenko\Settings\Providers;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/typed-settings.php', 'typed-settings');
    }

    public function boot()
    {

    }
}
