<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

abstract class BaseCredentialManager
{
    public function getAccessToken(): string
    {
        return $this->data('token');
    }

    public function getRefreshToken(): string
    {
        return $this->data('refresh_token');
    }

    public function getTenants(): ?array
    {
        return $this->data('tenants');
    }

    public function getTenantId(int $tenant = 0): string
    {
        if (! isset($this->data('tenants')[$tenant])) {
            throw new \Exception('No such tenant exists');
        }

        return $this->data('tenants')[$tenant]['Id'];
    }

    public function getExpires(): int
    {
        return $this->data('expires');
    }

    public function getData(): array
    {
        return $this->data();
    }

    public function getUser(): ?array
    {
        try {
            $jwt = new \XeroAPI\XeroPHP\JWTClaims();
            $jwt->setTokenId($this->data('id_token'));
            $decodedToken = $jwt->decode();

            return [
                'given_name' => $decodedToken->getGivenName(),
                'family_name' => $decodedToken->getFamilyName(),
                'email' => $decodedToken->getEmail(),
                'user_id' => $decodedToken->getXeroUserId(),
                'username' => $decodedToken->getPreferredUsername(),
                'session_id' => $decodedToken->getGlobalSessionId(),
            ];
        } catch (\Throwable $e) {
            return null;
        }
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
}
