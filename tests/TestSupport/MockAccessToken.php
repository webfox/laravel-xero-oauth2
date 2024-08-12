<?php

namespace Tests\Webfox\Xero\TestSupport;

use League\OAuth2\Client\Token\AccessTokenInterface;

class MockAccessToken implements AccessTokenInterface
{
    private string $token = 'token';

    public function getToken()
    {
        return $this->token;
    }

    public function setAccessToken(string $accessToken)
    {
        $this->token = $accessToken;

        return $this;
    }

    public function getRefreshToken()
    {
        return 'refresh-token';
    }

    public function getExpires()
    {
        return '1234';
    }

    public function hasExpired()
    {
        return false;
    }

    public function getValues()
    {
        return [
            'id_token' => [
                'token' => 'foo',
            ],
        ];
    }

    public function __toString()
    {
        return '';
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}