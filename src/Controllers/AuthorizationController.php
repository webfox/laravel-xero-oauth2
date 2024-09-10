<?php

namespace Webfox\Xero\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Webfox\Xero\OauthCredentialManager;

class AuthorizationController extends Controller
{
    public function __invoke(Redirector $redirect, OauthCredentialManager $oauth): RedirectResponse
    {
        return $redirect->to($oauth->getAuthorizationUrl());
    }
}
