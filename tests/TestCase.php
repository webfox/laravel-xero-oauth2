<?php

namespace Tests\Webfox\Xero;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Webfox\Xero\XeroServiceProvider;

class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app)
    {
        Http::preventStrayRequests();

        config()->set('app.key', 'base64:dW5venZpb3V2eDRkbHJjaHV3dDR5aW9mcnFpNzFrOTA=');

        $this->initializeDirectory($this->getTempDirectory());

        config()->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => $this->getTempDirectory(),
            'url' => '/tmp',
        ]);

        $app->bind('path.public', fn () => $this->getTempDirectory());
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    public function getTempDirectory(string $suffix = ''): string
    {
        return __DIR__.'/TestSupport/temp'.($suffix == '' ? '' : '/'.$suffix);
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