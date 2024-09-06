<?php

namespace Tests\Webfox\Xero\Unit\CredentialManagers;

use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\Mocks\MockAccessToken;
use Webfox\Xero\Oauth2CredentialManagers\CacheStore;
use Webfox\Xero\Oauth2Provider;

class CacheStoreTest extends TestCase
{
    public function test_you_can_get_cache_store_without_existing_data()
    {
        $sut = new CacheStore(app(Repository::class), app(Store::class), app(Oauth2Provider::class));

        $this->assertThrows(fn() => $sut->getAccessToken(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getRefreshToken(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getTenants(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getTenantId(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getExpires(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn() => $sut->getData(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertFalse($sut->exists());
        $this->assertThrows(fn() => $sut->isExpired(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertNull($sut->getUser());
    }

    public function test_you_can_get_cache_store_with_existing_data()
    {
        $cache = app(Repository::class);
        $cacheData = [
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
        ];

        $sut = new CacheStore($cache, app(Store::class), app(Oauth2Provider::class));
        $cache->put('xero_oauth', $cacheData);

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
        $this->assertEquals($cacheData, $sut->getData());
        $this->assertTrue($sut->exists());
        $this->assertFalse($sut->isExpired());
        $this->assertNull($sut->getUser());
    }

    public function test_that_authorization_sets_state_correctly()
    {
        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(['scope' => config('xero.oauth.scopes')])
                ->once()
                ->andReturn('https://example.com/foo');

            $mock->shouldReceive('getState')->andReturn('state');
        });

        $sut = new CacheStore(app(Repository::class), app(Store::class), app(Oauth2Provider::class));

        $this->assertEquals('https://example.com/foo', $sut->getAuthorizationUrl());
        $this->assertEquals('state', $sut->getState());
        $this->assertEquals('state', Session::get('xero_oauth'));
    }

    public function test_that_it_stores_data_correctly()
    {
        $sut = new CacheStore(app(Repository::class), app(Store::class), app(Oauth2Provider::class));

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
        $cache = app(Repository::class);
        $cache->put('xero_oauth', ['refresh_token' => 'default-refresh-token']);

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->with('refresh_token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => 'default-refresh-token',
                ])
                ->once()
                ->andReturn(new MockAccessToken());
        });

        $sut = new CacheStore($cache, app(Store::class), app(Oauth2Provider::class));

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

    public function test_you_can_get_user()
    {
        $cache = app(Repository::class);
        $cache->put('xero_oauth', [
            'id_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJnaXZlbl9uYW1lIjoiSmFtZXMgRnJlZW1hbiIsImZhbWlseV9uYW1lIjoiRnJlZW1hbiIsImVtYWlsIjoiZm9vQHRlc3QudGVzdCIsInVzZXJfaWQiOjEyMzQ1Njc4OSwidXNlcm5hbWUiOiJKYW1lc0ZyZWVtYW4iLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJKYW1lc0ZyZWVtYW4iLCJzZXNzaW9uX2lkIjoic2Vzc2lvbklkIiwic3ViIjoiMTIzNDU2Nzg5MCIsImlhdCI6MTUxNjIzOTAyMiwiZXhwIjoiIiwiYXV0aF90aW1lIjoiIiwiaXNzIjoiIiwiYXRfaGFzaCI6IiIsInNpZCI6IiIsImdsb2JhbF9zZXNzaW9uX2lkIjoiIiwieGVyb191c2VyaWQiOiIifQ.IcXMCuIOjgN-C-mJF2GXxsOhThc3_JOBFFi1m5e7LLg',
            'access_token' => 'james-secret-key',
        ]);

        $sut = new CacheStore($cache, app(Store::class), app(Oauth2Provider::class));

        $this->assertEquals([
            "given_name" => "James Freeman",
            "family_name" => "Freeman",
            "email" => "foo@test.test",
            "user_id" => "",
            "username" => "JamesFreeman",
            "session_id" => "",
        ], $sut->getUser());
    }
}