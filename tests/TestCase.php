<?php

namespace Tests\Webfox\Xero;

use Orchestra\Testbench\TestCase as Orchestra;
use Webfox\Xero\XeroServiceProvider;

class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('app.key', 'base64:dW5venZpb3V2eDRkbHJjaHV3dDR5aW9mcnFpNzFrOTA=');
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/TestSupport/Migrations/create_users_table.php';
        $migration->up();
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            XeroServiceProvider::class,
        ];
    }
}
