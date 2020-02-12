<?php


namespace Webfox\Xero;


use Illuminate\Session\Store;
use Illuminate\Cache\Repository;
use League\OAuth2\Client\Token\AccessTokenInterface;

class OauthCredentialManager
{
    /** @var Repository  */
    protected $cache;

    /** @var Oauth2Provider  */
    protected $oauthProvider;

    /** @var Store */
    protected $session;

    protected $cacheKey = 'xero_oauth';

    public function __construct(Repository $cache, Store $session, Oauth2Provider $oauthProvider)
    {
        $this->cache         = $cache;
        $this->oauthProvider = $oauthProvider;
        $this->session       = $session;
    }

    public function getAccessToken(): string
    {
        return $this->data('token');
    }

    public function getRefreshToken(): string
    {
        return $this->data('refresh_token');
    }

    public function getTenantId()
    {
        return $this->data('tenant_id');
    }

    public function getExpires(): int
    {
        return $this->data('expires');
    }

    public function getState()
    {
        return $this->session->get($this->cacheKey);
    }

    public function getAuthorizationUrl()
    {
        $redirectUrl = $this->oauthProvider->getAuthorizationUrl(['scope' => implode(' ', config('xero.oauth.scopes'))]);
        $this->session->put($this->cacheKey, $this->oauthProvider->getState());

        return $redirectUrl;
    }

    public function getData()
    {
        return $this->data();
    }

    public function exists()
    {
        return $this->cache->has($this->cacheKey);
    }

    public function isExpired(): bool
    {
        return time() >= $this->data('expires');
    }

    public function refresh()
    {
        $newAccessToken = $this->oauthProvider->getAccessToken('refresh_token', [
            'refresh_token' => $this->getRefreshToken(),
        ]);

        $this->store($newAccessToken);
    }

    public function store(AccessTokenInterface $token, $tenantId = null)
    {
        $this->cache->set($this->cacheKey, [
            'token'         => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token'      => $token->getValues()['id_token'],
            'expires'       => $token->getExpires(),
            'tenant_id'     => $tenantId ?? $this->data('tenant_id')
        ]);
    }

    public function getUser()
    {
        $jwt = new \XeroAPI\XeroPHP\JWTClaims();
        $jwt->setTokenId($this->data('id_token'));
        $decodedToken = $jwt->decode();

        return [
            'given_name'  => $decodedToken->getGivenName(),
            'family_name' => $decodedToken->getFamilyName(),
            'email'       => $decodedToken->getEmail(),
            'user_id'     => $decodedToken->getXeroUserId(),
            'username'    => $decodedToken->getPreferredUsername(),
            'session_id'  => $decodedToken->getGlobalSessionId()
        ];
    }

    protected function data($key = null)
    {
        if (!$this->exists()) {
            throw new \Exception('Xero oauth credentials are missing');
        }

        $cacheData = $this->cache->get($this->cacheKey);
        return empty($key) ? $cacheData : ($cacheData[$key] ?? null);
    }
}