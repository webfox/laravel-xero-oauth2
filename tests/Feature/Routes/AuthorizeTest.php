<?php

namespace Tests\Webfox\Xero\Feature\Routes;

use Tests\Webfox\Xero\TestCase;

class AuthorizeTest extends TestCase
{
    public function test_that_you_can_get_authorize_route()
    {
        $response = $this->get(route('xero.auth.authorize'))
            ->assertRedirect();

        $url = parse_url($response->headers->get('location'));
        parse_str($url['query'], $query);

        $this->assertEquals('https', $url['scheme']);
        $this->assertEquals('login.xero.com', $url['host']);
        $this->assertEquals('/identity/connect/authorize', $url['path']);

        $this->assertArrayHasKey('state', $query);
        $this->assertEquals('openid email profile offline_access accounting.settings', $query['scope']);
        $this->assertEquals('code', $query['response_type']);
        $this->assertEquals('demo-client-id', $query['client_id']);
        $this->assertEquals('http://localhost/xero/auth/callback', $query['redirect_uri']);
    }
}