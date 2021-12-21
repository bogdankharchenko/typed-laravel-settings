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
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate', ['--database' => 'testing']);

        include_once __DIR__ . '/../database/migrations/2021_10_12_090425_set_up_settings_module.php';
        (new \SetUpSettingsModule())->up();
    }

    protected function getPackageProviders($app)
    {
        return [
            SettingsServiceProvider::class,
        ];
    }
}
