<?php

namespace BogdanKharchenko\Settings\Tests;

use BogdanKharchenko\Settings\Providers\SettingsServiceProvider;
use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('cache.stores.redis', [
            'driver'     => 'redis',
            'connection' => 'cache',
        ]);

        $app['config']->set('cache.stores.array', [
            'driver'     => 'array',
            'connection' => 'cache',
        ]);
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
