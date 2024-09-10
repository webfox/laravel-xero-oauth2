<?php

namespace Tests\Webfox\Xero\TestSupport;

use Illuminate\Support\Facades\Config;
use Webfox\Xero\Clients\AccountAPIClient;
use Webfox\Xero\Clients\IdentityAPIClient;
use Webfox\Xero\Oauth2CredentialManagers\ArrayStore;

class XeroOAuth
{
    public static function fake()
    {
        Config::set('xero.credential_store', ArrayStore::class);

        IdentityAPIClient::fake();
        AccountAPIClient::fake();
    }
}
