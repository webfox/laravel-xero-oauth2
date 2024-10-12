<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Support\Facades\Auth;
use Webfox\Xero\Exceptions\XeroUserNotAuthenticated;

class AuthenticatedUserStore extends ModelStore
{
    /**
     * @throws XeroUserNotAuthenticated
     */
    public function __construct()
    {
        if (! Auth::check()) {
            throw XeroUserNotAuthenticated::make();
        }

        parent::__construct();

        $this->model = Auth::user();
    }
}
