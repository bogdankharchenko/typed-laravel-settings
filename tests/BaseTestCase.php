<?php

namespace BogdanKharchenko\Settings\Tests;

use BogdanKharchenko\Settings\Providers\SettingsServiceProvider;
use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:qRUMF9sMqjvlgHotD/FkjkClPjGjgYNPY6E0NNGNiZM=');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.stores.redis', [
            'driver' => 'redis',
            'connection' => 'cache',
        ]);

        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'connection' => 'cache',
        ]);

        // Force the package's cacher onto the in-memory array store so the
        // test suite doesn't reach out to Redis (the workflow no longer
        // provisions a Redis service).
        $app['config']->set('typed-settings.cache.store', 'array');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->artisan('migrate', [ '--database' => 'testbench' ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            SettingsServiceProvider::class,
        ];
    }
}
