<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Cache\Repository;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Exceptions\XeroCredentialsNotFound;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;

class CacheStore extends BaseCredentialManager implements OauthCredentialManager
{
    protected string $cacheKey = 'xero_oauth';

    protected Repository $cache;

    public function __construct()
    {
        $this->cache = app(Repository::class);

        parent::__construct();
    }

    public function exists(): bool
    {
        return $this->cache->has($this->cacheKey);
    }

    public function store(AccessTokenInterface $token, array $tenants = null): void
    {
        $this->cache->forever($this->cacheKey, [
            'token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token' => $token->getValues()['id_token'],
            'expires' => $token->getExpires(),
            'tenants' => $tenants ?? $this->getTenants(),
        ]);
    }

    protected function data(string $key = null)
    {
        if (! $this->exists()) {
            throw new XeroCredentialsNotFound('Xero oauth credentials are missing');
        }

        $cacheData = $this->cache->get($this->cacheKey);

        return empty($key) ? $cacheData : ($cacheData[$key] ?? null);
    }
}
