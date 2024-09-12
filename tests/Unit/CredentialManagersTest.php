<?php

namespace Tests\Webfox\Xero\Unit;

use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\Mocks\MockAccessToken;
use Webfox\Xero\ActiveXeroModel;
use Webfox\Xero\Oauth2CredentialManagers\ArrayStore;
use Webfox\Xero\Oauth2CredentialManagers\AuthenticatedUserStore;
use Webfox\Xero\Oauth2CredentialManagers\CacheStore;
use Webfox\Xero\Oauth2CredentialManagers\FileStore;
use Webfox\Xero\Oauth2CredentialManagers\ModelStore;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;

class CredentialManagersTest extends TestCase
{
    #[DataProvider('credentialManagers')]
    public function test_you_can_get_file_store_without_existing_data($sutClass, $dependencies, $setupFunction, $createExistingData)
    {
        $setupFunction();

        $sut = new $sutClass(...$this->loadDependencies($dependencies));

        $this->assertThrows(fn () => $sut->getAccessToken(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn () => $sut->getRefreshToken(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn () => $sut->getTenants(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn () => $sut->getTenantId(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn () => $sut->getExpires(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertThrows(fn () => $sut->getData(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertFalse($sut->exists());
        $this->assertThrows(fn () => $sut->isExpired(), Exception::class, 'Xero oauth credentials are missing');
        $this->assertNull($sut->getUser());
    }

    #[DataProvider('credentialManagers')]
    public function test_you_can_get_file_store_with_existing_data($sutClass, $dependencies, $setupFunction, $createExistingData)
    {
        $setupFunction();

        $sut = new $sutClass(...$this->loadDependencies($dependencies));

        $createExistingData($sut, $existingData = [
            'token' => 'default-token',
            'refresh_token' => 'default-refresh-token',
            'id_token' => [
                'token' => 'foo',
            ],
            'expires' => $expires = strtotime('+1 hour'),
            'tenants' => [
                [
                    'Id' => '123',
                    'tenant' => 'tenant_id',
                    'expires' => 3600,
                ],
            ],
        ]);

        $this->assertEquals('default-token', $sut->getAccessToken());
        $this->assertEquals('default-refresh-token', $sut->getRefreshToken());
        $this->assertEquals([
            [
                'Id' => '123',
                'tenant' => 'tenant_id',
                'expires' => 3600,
            ],
        ], $sut->getTenants());
        $this->assertEquals('123', $sut->getTenantId());
        $this->assertEquals($expires, $sut->getExpires());
        $this->assertEquals($existingData, $sut->getData());
        $this->assertTrue($sut->exists());
        $this->assertFalse($sut->isExpired());
        $this->assertNull($sut->getUser());
    }

    #[DataProvider('credentialManagers')]
    public function test_that_authorization_sets_state_correctly($sutClass, $dependencies, $setupFunction, $createExistingData)
    {
        $setupFunction();

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->with(['scope' => config('xero.oauth.scopes')])
                ->once()
                ->andReturn('https://example.com/foo');

            $mock->shouldReceive('getState')->andReturn('state');
        });

        $sut = new $sutClass(...$this->loadDependencies($dependencies));

        $this->assertEquals('https://example.com/foo', $sut->getAuthorizationUrl());
        $this->assertEquals('state', $sut->getState());
        $this->assertEquals('state', Session::get('xero_oauth2_state'));
    }

    #[DataProvider('credentialManagers')]
    public function test_that_it_stores_data_correctly($sutClass, $dependencies, $setupFunction, $createExistingData)
    {
        $setupFunction();

        $sut = new $sutClass(...$this->loadDependencies($dependencies));

        $sut->store(new MockAccessToken(), ['tenant' => 'tenant_id', 'expires' => 3600]);

        $this->assertEquals([
            'token' => 'token',
            'refresh_token' => 'refresh-token',
            'id_token' => [
                'token' => 'foo',
            ],
            'expires' => '1234',
            'tenants' => [
                'tenant' => 'tenant_id',
                'expires' => 3600,
            ],
        ], $sut->getData());
    }

    #[DataProvider('credentialManagers')]
    public function test_that_it_can_refresh_its_token($sutClass, $dependencies, $setupFunction, $createExistingData)
    {
        $setupFunction();

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->with('refresh_token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => 'default-refresh-token',
                ])
                ->once()
                ->andReturn(new MockAccessToken());
        });

        $sut = new $sutClass(...$this->loadDependencies($dependencies));

        $createExistingData($sut, ['refresh_token' => 'default-refresh-token']);

        $sut->refresh();

        $this->assertEquals([
            'token' => 'token',
            'refresh_token' => 'refresh-token',
            'id_token' => [
                'token' => 'foo',
            ],
            'expires' => '1234',
            'tenants' => null,
        ], $sut->getData());
    }

    #[DataProvider('credentialManagers')]
    public function test_you_can_get_user($sutClass, $dependencies, $setupFunction, $createExistingData)
    {
        $setupFunction();

        $sut = new $sutClass(...$this->loadDependencies($dependencies));

        $createExistingData($sut, [
            'id_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJnaXZlbl9uYW1lIjoiSmFtZXMgRnJlZW1hbiIsImZhbWlseV9uYW1lIjoiRnJlZW1hbiIsImVtYWlsIjoiZm9vQHRlc3QudGVzdCIsInVzZXJfaWQiOjEyMzQ1Njc4OSwidXNlcm5hbWUiOiJKYW1lc0ZyZWVtYW4iLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJKYW1lc0ZyZWVtYW4iLCJzZXNzaW9uX2lkIjoic2Vzc2lvbklkIiwic3ViIjoiMTIzNDU2Nzg5MCIsImlhdCI6MTUxNjIzOTAyMiwiZXhwIjoiIiwiYXV0aF90aW1lIjoiIiwiaXNzIjoiIiwiYXRfaGFzaCI6IiIsInNpZCI6IiIsImdsb2JhbF9zZXNzaW9uX2lkIjoiIiwieGVyb191c2VyaWQiOiIifQ.IcXMCuIOjgN-C-mJF2GXxsOhThc3_JOBFFi1m5e7LLg',
            'access_token' => 'james-secret-key',
        ]);

        $this->assertEquals([
            'given_name' => 'James Freeman',
            'family_name' => 'Freeman',
            'email' => 'foo@test.test',
            'user_id' => '',
            'username' => 'JamesFreeman',
            'session_id' => '',
        ], $sut->getUser());
    }

    public static function credentialManagers(): array
    {
        return [
            'fileStore' => [
                'sutClass' => FileStore::class,
                'dependencies' => [
                    FilesystemManager::class,
                    Store::class,
                    Oauth2Provider::class,
                ],
                'setupFunction' => fn () => Storage::fake(),
                'createExistingData' => fn (OauthCredentialManager $credentialManager, $data) => Storage::put('xero.json', json_encode($data)),
            ],

            'cacheStore' => [
                'sutClass' => CacheStore::class,
                'dependencies' => [
                    Repository::class,
                    Store::class,
                    Oauth2Provider::class,
                ],
                'setupFunction' => fn () => null,
                'createExistingData' => fn (OauthCredentialManager $credentialManager, $data) => app(Repository::class)->put('xero_oauth', $data),
            ],

            'arrayStore' => [
                'sutClass' => ArrayStore::class,
                'dependencies' => [
                    Store::class,
                    Oauth2Provider::class,
                ],
                'setupFunction' => fn () => null,
                'createExistingData' => fn (OauthCredentialManager $credentialManager, $data) => $credentialManager->dataStorage = $data,
            ],

            'modelStore' => [
                'sutClass' => ModelStore::class,
                'dependencies' => [
                    Store::class,
                    Oauth2Provider::class,
                ],
                'setupFunction' => fn () => app(ActiveXeroModel::class)->setActiveModel(User::create()),
                'createExistingData' => function (OauthCredentialManager $credentialManager, $data) {
                    app(ActiveXeroModel::class)->getModel()->update(['xero_credentials' => $data]);
                },
            ],

            'authenticatedUserStore' => [
                'sutClass' => AuthenticatedUserStore::class,
                'dependencies' => [
                    Store::class,
                    Oauth2Provider::class,
                ],
                'setupFunction' => fn () => auth()->login(User::create()),
                'createExistingData' => function (OauthCredentialManager $credentialManager, $data) {
                    $credentialManager->model->update(['xero_credentials' => $data]);
                },
            ],
        ];
    }

    private function loadDependencies(array $dependencies): array
    {
        return collect($dependencies)
            ->map(fn ($dependency) => app($dependency))
            ->toArray();
    }
}

class User extends Authenticatable
{
    protected function casts()
    {
        return [
            'xero_credentials' => 'array',
        ];
    }

    protected $fillable = ['xero_credentials'];
}
