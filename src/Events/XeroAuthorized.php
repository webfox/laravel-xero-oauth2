<?php

namespace Webfox\Xero\Events;

class XeroAuthorized
{

    public $data;
    public $token;
    public $refresh_token;
    public $id_token;
    public $expires;
    public $tenant_id;

    public function __construct($data)
    {
        $this->token         = $data['token'];
        $this->refresh_token = $data['refresh_token'];
        $this->id_token      = $data['id_token'];
        $this->expires       = $data['expires'];
        $this->tenants       = $data['tenants'];
    }
}
