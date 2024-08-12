<?php

namespace Webfox\Xero\Events;

class XeroAuthorized
{
    public string $token;
    public string $refresh_token;
    public string $id_token;
    public string $expires;
    public string $tenants;

    public function __construct(public array $data)
    {
        $this->token         = $data['token'];
        $this->refresh_token = $data['refresh_token'];
        $this->id_token      = $data['id_token'];
        $this->expires       = $data['expires'];
        $this->tenants       = $data['tenants'];
    }
}
