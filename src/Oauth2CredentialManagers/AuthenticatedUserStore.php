<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Webfox\Xero\Exceptions\XeroCredentialsNotFound;
use Webfox\Xero\Exceptions\XeroUserNotAuthenticated;
use Webfox\Xero\Oauth2Provider;

class AuthenticatedUserStore extends ModelStore
{
    public function __construct()
    {
        if (! Auth::check()) {
            throw new XeroUserNotAuthenticated('User is not authenticated');
        }

        BaseCredentialManager::__construct();

        $this->model = Auth::user();
    }
}
