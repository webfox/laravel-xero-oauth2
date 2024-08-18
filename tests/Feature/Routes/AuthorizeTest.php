<?php

namespace Tests\Webfox\Xero\Feature\Routes;

use Mockery\MockInterface;
use Tests\Webfox\Xero\TestCase;
use Webfox\Xero\OauthCredentialManager;

class AuthorizeTest extends TestCase
{
    public function test_that_you_can_get_authorize_route()
    {
        $this->mock(OauthCredentialManager::class, function(MockInterface $mock){
            $mock->shouldReceive('getAuthorizationUrl')
                ->once()
                ->andReturn('https://example.com/foo');
        });

        $this->get(route('xero.auth.authorize'))
            ->assertRedirect('https://example.com/foo');
    }
}