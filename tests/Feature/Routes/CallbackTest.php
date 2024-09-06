<?php

namespace Tests\Webfox\Xero\Feature\Routes;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\Mocks\MockAccessToken;
use Tests\Webfox\Xero\TestSupport\XeroOAuth;
use Webfox\Xero\Clients\IdentityAPIClient;
use Webfox\Xero\Events\XeroAuthorized;
use Webfox\Xero\Exceptions\OAuthException;
use Webfox\Xero\Oauth2Provider;

class CallbackTest extends TestCase
{
    public function test_you_can_get_callback_url()
    {
        Event::fake();
        XeroOAuth::fake();

        Session::put('xero_oauth2_state', 'foo');

        IdentityAPIClient::getHttpClient()
            ->shouldReceive('send')
            ->withArgs(function (Request $request) {
                $this->assertEquals('GET', $request->getMethod());
                $this->assertEquals('https://api.xero.com/Connections', (string)$request->getUri());
                $this->assertEquals('application/json', $request->getHeader('Content-Type')[0]);

                return true;
            })
            ->once()
            ->andReturn(new Response(200, [], json_encode([[
                "id" => 'id',
                "tenantId" => 'tenant_id',
                "authEventId" => 'auth_event_id',
                "tenantType" => 'tenant_type',
                "tenantName" => 'tenant_name',
            ]])));

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->andReturn(new MockAccessToken);
        });

        Route::get('/xero/success', function () {
        })->name('xero.auth.success');

        $this->get(route('xero.auth.callback', [
            'state' => 'foo',
            'code' => 'bar',
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute('xero.auth.success');

        Event::assertDispatched(XeroAuthorized::class, function (XeroAuthorized $event) {
            $this->assertEquals('token', $event->token);
            $this->assertEquals('refresh-token', $event->refresh_token);
            $this->assertEquals(['token' => 'foo'], $event->id_token);
            $this->assertEquals('1234', $event->expires);
            $this->assertEquals([
                [
                    'Id' => 'tenant_id',
                    'Name' => 'tenant_name',
                    'ConnectionId' => 'id',
                ]
            ], $event->tenants);

            return true;
        });
    }

    public function test_if_state_does_not_match_it_will_error()
    {
        Event::fake();
        XeroOAuth::fake();

        Session::put('xero_oauth2_state', 'nope');

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->andReturn(new MockAccessToken);
        });

        $this->from($from = route('xero.auth.authorize'))
            ->get(route('xero.auth.callback', [
                'state' => 'foo',
                'code' => 'bar',
            ]))
            ->assertRedirect($from)
            ->assertSessionHasErrors('state');

        Event::assertNotDispatched(XeroAuthorized::class);
    }

    public function test_if_there_is_an_error_an_oauth_exception_will_be_thrown()
    {
        Event::fake();
        XeroOAuth::fake();

        Session::put('xero_oauth2_state', 'foo');

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->andReturn(new MockAccessToken);
        });

        $this->withoutExceptionHandling();

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Error: Error Description');

        $this->get(route('xero.auth.callback', [
                'state' => 'foo',
                'code' => 'bar',
                'error' => 'error',
                'error_description' => 'error description',
            ]));

        Event::assertNotDispatched(XeroAuthorized::class);
    }

    public function test_that_it_will_handle_on_failure()
    {
        Event::fake();
        XeroOAuth::fake();

        Session::put('xero_oauth2_state', 'foo');

        $this->mock(Oauth2Provider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAccessToken')
                ->andThrow(new Exception('foo'));
        });

        $this->withoutExceptionHandling();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('foo');

        $this->get(route('xero.auth.callback', [
                'state' => 'foo',
                'code' => 'bar',
            ]))
            ->assertInternalServerError();

        Event::assertNotDispatched(XeroAuthorized::class);
    }
}