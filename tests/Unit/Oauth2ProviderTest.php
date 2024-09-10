<?php

namespace Tests\Webfox\Xero\Unit;

use League\OAuth2\Client\Provider\GenericProvider;
use Tests\Webfox\Xero\TestCase;
use Webfox\Xero\Oauth2Provider;

class Oauth2ProviderTest extends TestCase
{
    public function test_that_oauth2_provider_is_correct()
    {
        $sut = new Oauth2Provider([
            'clientId' => '',
            'clientSecret' => '',
            'redirectUri' => '',
            'urlAuthorize' => '',
            'urlAccessToken' => '',
            'urlResourceOwnerDetails' => '',
        ]);

        $this->assertInstanceOf(GenericProvider::class, $sut);
    }
}
