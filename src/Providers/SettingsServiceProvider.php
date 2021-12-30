<?php

namespace BogdanKharchenko\Settings\Providers;

use BogdanKharchenko\Settings\Repository\Encrypter;
use BogdanKharchenko\Settings\Repository\Validator;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('typed-settings.encrypter', function ($app) {
            $encrypter = config('typed-settings.encrypter') ?? Encrypter::class;

            return new $encrypter();
        });

        $this->app->singleton('typed-settings.validator', function ($app) {
            $validator = config('typed-settings.validator') ?? Validator::class;

            return new $validator();
        });

        $this->mergeConfigFrom(__DIR__.'/../../config/typed-settings.php', 'typed-settings');
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishes([
            __DIR__.'/../../config/typed-settings.php' => config_path('typed-settings.php'),
        ]);
    }
}
