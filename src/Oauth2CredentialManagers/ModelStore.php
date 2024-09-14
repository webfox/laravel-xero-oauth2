<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Store;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;
use Webfox\Xero\Xero;

class ModelStore extends BaseCredentialManager implements OauthCredentialManager
{
    public Model $model;

    public function __construct(protected Store $session, protected Oauth2Provider $oauthProvider)
    {
        $this->model = Xero::getModelStorage();
    }

    public function exists(): bool
    {
        return $this->model && $this->model->exists && is_array($this->model->{$this->getModelKey()});
    }

    public function store(AccessTokenInterface $token, array $tenants = null): void
    {
        $this->model->update([
            $this->getModelKey() => [
                'token' => $token->getToken(),
                'refresh_token' => $token->getRefreshToken(),
                'id_token' => $token->getValues()['id_token'],
                'expires' => $token->getExpires(),
                'tenants' => $tenants ?? $this->getTenants(),
            ],
        ]);
    }

    protected function data(string $key = null)
    {
        if (! $this->exists()) {
            throw new \Exception('Xero oauth credentials are missing');
        }

        $data = $this->model->{$this->getModelKey()};

        return $key === null ? $data : $data[$key] ?? null;
    }

    private function getModelKey(): string
    {
        return Xero::getModelAttribute();
    }
}
