<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Support\Facades\Auth;
use Webfox\Xero\Exceptions\XeroUserNotAuthenticated;
use Webfox\Xero\Xero;

class AuthenticatedUserStore extends ModelStore
{
    public function __construct()
    {
        $auth = Auth::guard(Xero::getDefaultAuthGuard());

        if (! $auth->check()) {
            throw new XeroUserNotAuthenticated('User is not authenticated');
        }

        parent::__construct();

        $this->model = $auth->user();
    }
}
