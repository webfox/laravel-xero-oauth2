<?php


namespace Webfox\Xero\Oauth2CredentialManagers;


use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Session\Store;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;

class FileStore implements OauthCredentialManager
{
    /** @var FilesystemManager  */
    protected $files;

    /** @var Oauth2Provider  */
    protected $oauthProvider;

    /** @var Store */
    protected $session;

    /** @var string */
    protected $filePath;

    public function __construct(FilesystemManager $files, Store $session, Oauth2Provider $oauthProvider)
    {
        $this->files         = $files;
        $this->oauthProvider = $oauthProvider;
        $this->session       = $session;
        $this->filePath      = 'xero.json';
    }

    public function getAccessToken(): string
    {
        return $this->data('token');
    }

    public function getRefreshToken(): string
    {
        return $this->data('refresh_token');
    }

    public function getTenantId(): string
    {
        return $this->data('tenant_id');
    }

    public function getExpires(): int
    {
        return $this->data('expires');
    }

    public function getState(): string
    {
        return $this->session->get('xero_oauth2_state');
    }

    public function getAuthorizationUrl(): string
    {
        $redirectUrl = $this->oauthProvider->getAuthorizationUrl(['scope' => config('xero.oauth.scopes')]);
        $this->session->put('xero_oauth2_state', $this->oauthProvider->getState());

        return $redirectUrl;
    }

    public function getData(): array
    {
        return $this->data();
    }

    public function exists(): bool
    {
        return $this->files->disk(config('xero.credential_disk'))->exists($this->filePath);
    }

    public function isExpired(): bool
    {
        return time() >= $this->data('expires');
    }

    public function refresh(): void
    {
        $newAccessToken = $this->oauthProvider->getAccessToken('refresh_token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken(),
        ]);

        $this->store($newAccessToken);
    }

    public function store(AccessTokenInterface $token, string $tenantId = null): void
    {
        $ret = $this->files->disk(config('xero.credential_disk'))->put($this->filePath, json_encode([
            'token'         => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token'      => $token->getValues()['id_token'],
            'expires'       => $token->getExpires(),
            'tenant_id'     => $tenantId ?? $this->getTenantId()
        ]), 'private');

        if ($ret === false) {
            throw new \Exception("Failed to write to file: {$this->filePath}");
        }
    }

    public function getUser(): ?array
    {

        try {
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
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function data($key = null)
    {
        if (!$this->exists()) {
            throw new \Exception('Xero oauth credentials are missing');
        }

        $cacheData = json_decode($this->files->disk(config('xero.credential_disk'))->get($this->filePath), true);

        return empty($key) ? $cacheData : ($cacheData[$key] ?? null);
    }
}
