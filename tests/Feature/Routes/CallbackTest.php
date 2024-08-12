<?php

namespace Tests\Webfox\Xero\Feature\Routes;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\MockAccessToken;
use Webfox\Xero\Oauth2Provider;
use XeroAPI\XeroPHP\Api\IdentityApi;

class CallbackTest extends TestCase
{
    public function test_you_can_get_callback_url()
    {
        $this->markTestSkipped('This test has not been implemented yet.');

        Session::put('xero_oauth2_state', 'bar');

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->with('authorization_code', ['code' => 'bar'])
                ->once()
                ->andReturn(new MockAccessToken());
        });

        $this->mock(IdentityApi::class, function (MockInterface $mock) {
            $mock->shouldReceive('getConfig')->once()->andReturn($this->getConfigMock());
            $mock->shouldReceive('getConnections')->once()->andReturn($this->getConnectionsMock());
        });

        $response = $this->get(route('xero.auth.callback', [
            'state' => 'foo',
            'code' => 'bar',
        ]))->assertOk();

    }

    private function getConfigMock()
    {
        return new class()
        {
            public function setAccessToken(string $accessToken)
            {
            }
        };
    }

    private function getConnectionsMock()
    {
        return [
            new class()
            {
                public function getId()
                {
                    return 'id';
                }
                public function getTenantId()
                {
                    return 'tenant-id';
                }

                public function getTenantName()
                {
                    return 'tenant-name';
                }
            },
        ];
    }
}