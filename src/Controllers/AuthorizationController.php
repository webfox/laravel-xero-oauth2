<?php

namespace Webfox\Xero\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Webfox\Xero\OauthCredentialManager;

class AuthorizationController extends Controller
{
    public function __invoke(Redirector $redirect, OauthCredentialManager $oauth)
    {
        return $redirect->to($oauth->getAuthorizationUrl());
    }

}
