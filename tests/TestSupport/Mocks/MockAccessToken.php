<?php

namespace Tests\Webfox\Xero\TestSupport\Mocks;

use Illuminate\Contracts\Support\Arrayable;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Tests\Webfox\Xero\TestSupport\ReturnTypeWillChange;

class MockAccessToken implements AccessTokenInterface, Arrayable
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
            'id_token' => 'foo'
        ];
    }

    public function toArray()
    {
        return [
            'token' => $this->getToken(),
            'refresh_token' => $this->getRefreshToken(),
            'id_token' => $this->getValues()['id_token'],
            'expires' => $this->getExpires(),
        ];
    }

    public function __toString()
    {
        return '';
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return json_encode($this->toArray());
    }
}
