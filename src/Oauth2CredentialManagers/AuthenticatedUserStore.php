<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Webfox\Xero\Oauth2Provider;

class AuthenticatedUserStore extends ModelStore
{
    public function __construct(protected Store $session, protected Oauth2Provider $oauthProvider)
    {
        if (! Auth::check()) {
            throw new \Exception('User is not authenticated');
        }

        $this->model = Auth::user();
    }
}
