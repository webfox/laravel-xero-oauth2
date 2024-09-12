<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Session\Store;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;

class FileStore extends BaseCredentialManager implements OauthCredentialManager
{
    /** @var FilesystemManager */
    protected $disk;

    /** @var string */
    protected string $filePath;

    public function __construct(protected FilesystemManager $files, protected Store $session, protected Oauth2Provider $oauthProvider)
    {
        $this->disk = $files->disk(config('xero.credential_disk', config('filesystems.default')));
        $this->filePath = 'xero.json';
    }

    public function exists(): bool
    {
        return $this->disk->exists($this->filePath);
    }

    public function store(AccessTokenInterface $token, array $tenants = null): void
    {
        $ret = $this->disk->put($this->filePath, json_encode([
            'token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token' => $token->getValues()['id_token'],
            'expires' => $token->getExpires(),
            'tenants' => $tenants ?? $this->getTenants(),
        ]), 'private');

        if ($ret === false) {
            throw new \Exception("Failed to write to file: {$this->filePath}");
        }
    }

    protected function data(string $key = null)
    {
        if (! $this->exists()) {
            throw new \Exception('Xero oauth credentials are missing');
        }

        $cacheData = json_decode($this->disk->get($this->filePath), true);

        return empty($key) ? $cacheData : ($cacheData[$key] ?? null);
    }
}
