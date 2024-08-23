<?php

namespace Tests\Webfox\Xero\Unit\CredentialManagers;

use Exception;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\Mocks\MockAccessToken;
use Webfox\Xero\Oauth2CredentialManagers\FileStore;
use Webfox\Xero\Oauth2Provider;

class FileStoreTest extends TestCase
{
    public function test_you_can_get_array_store_without_existing_data()
    {
        Storage::fake();

        $sut = new FileStore(app(FilesystemManager::class), app(Store::class), app(Oauth2Provider::class));

        $this->assertThrows(fn() => $sut->getAccessToken(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getRefreshToken(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getTenants(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getTenantId(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getExpires(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getData(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertFalse($sut->exists());
        $this->assertThrows(fn() => $sut->isExpired(), Exception::class, 'Xero oauth credentials are missing');
    }

    public function test_you_can_get_array_store_with_existing_data()
    {
        Storage::fake();

        $this->createStorageFile($existingData = [
            'token' => 'default-token',
            'refresh_token' => 'default-refresh-token',
            'id_token' => [
                'token' => 'foo'
            ],
            'expires' => $expires = strtotime('+1 hour'),
            'tenants' => [
                [
                    'Id' => '123',
                    'tenant' => 'tenant_id',
                    'expires' => 3600
                ]
            ],
        ]);

        $sut = new FileStore(app(FilesystemManager::class), app(Store::class), app(Oauth2Provider::class));

        $this->assertEquals('default-token', $sut->getAccessToken());
        $this->assertEquals('default-refresh-token', $sut->getRefreshToken());
        $this->assertEquals([
            [
                'Id' => '123',
                'tenant' => 'tenant_id',
                'expires' => 3600
            ]
        ], $sut->getTenants());
        $this->assertEquals('123', $sut->getTenantId());
        $this->assertEquals($expires, $sut->getExpires());
        $this->assertEquals($existingData, $sut->getData());
        $this->assertTrue($sut->exists());
        $this->assertFalse($sut->isExpired());
    }

    public function test_that_authorization_sets_state_correctly()
    {
        Storage::fake();

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(['scope' => config('xero.oauth.scopes')])
                ->once()
                ->andReturn('https://example.com/foo');

            $mock->shouldReceive('getState')->andReturn('state');
        });

        $sut = new FileStore(app(FilesystemManager::class), app(Store::class), app(Oauth2Provider::class));

        $this->assertEquals('https://example.com/foo', $sut->getAuthorizationUrl());
        $this->assertEquals('state', $sut->getState());
        $this->assertEquals('state', Session::get('xero_oauth2_state'));
    }

    public function test_that_it_stores_data_correctly()
    {
        Storage::fake();

        $sut = new FileStore(app(FilesystemManager::class), app(Store::class), app(Oauth2Provider::class));

        $sut->store(new MockAccessToken(), ['tenant' => 'tenant_id', 'expires' => 3600]);

        $this->assertEquals([
            'token' => 'token',
            'refresh_token' => 'refresh-token',
            'id_token' => [
                'token' => 'foo'
            ],
            'expires' => '1234',
            'tenants' => [
                'tenant' => 'tenant_id',
                'expires' => 3600
            ],
        ], $sut->getData());
    }

    public function test_that_it_can_refresh_its_token()
    {
        Storage::fake();

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->with('refresh_token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => 'default-refresh-token',
                ])
                ->once()
                ->andReturn(new MockAccessToken());
        });

        $sut = new FileStore(app(FilesystemManager::class), app(Store::class), app(Oauth2Provider::class));

        $this->createStorageFile(['refresh_token' => 'default-refresh-token']);

        $sut->refresh();

        $this->assertEquals([
            'token' => 'token',
            'refresh_token' => 'refresh-token',
            'id_token' => [
                'token' => 'foo'
            ],
            'expires' => '1234',
            'tenants' => null,
        ], $sut->getData());
    }

    private function createStorageFile(array $array): void
    {
        Storage::put('xero.json', json_encode($array));
    }
}