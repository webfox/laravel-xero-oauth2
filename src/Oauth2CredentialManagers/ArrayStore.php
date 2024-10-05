<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Session\Store;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Exceptions\XeroCredentialsNotFound;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;

class ArrayStore extends BaseCredentialManager implements OauthCredentialManager
{
    public ?array $dataStorage = null;

    public function exists(): bool
    {
        return $this->dataStorage !== null;
    }

    public function store(AccessTokenInterface $token, array $tenants = null): void
    {
        $this->dataStorage = [
            'token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token' => $token->getValues()['id_token'],
            'expires' => $token->getExpires(),
            'tenants' => $tenants ?? $this->getTenants(),
        ];
    }

    protected function data(string $key = null)
    {
        if (! $this->exists()) {
            throw new XeroCredentialsNotFound('Xero oauth credentials are missing');
        }

        return $key === null ? $this->dataStorage : $this->dataStorage[$key] ?? null;
    }
}
