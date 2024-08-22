<?php

namespace Webfox\Xero\Controllers;

use Illuminate\Http\Request;
use Webfox\Xero\Oauth2Provider;
use Illuminate\Routing\Controller;
use XeroAPI\XeroPHP\Api\IdentityApi;
use Illuminate\Support\Facades\Event;
use Webfox\Xero\Events\XeroAuthorized;
use Webfox\Xero\OauthCredentialManager;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Str;
use Webfox\Xero\Exceptions\OAuthException;

class AuthorizationCallbackController extends Controller
{
    use ValidatesRequests;

    public function __invoke(Request $request, OauthCredentialManager $oauth, IdentityApi $identity, Oauth2Provider $provider)
    {
        try {
            $this->validate($request, [
                'error' => ['sometimes', 'required', 'string'],
                'error_description' => ['required_with:error', 'string'],
                'code'  => ['required_if:error,null', 'string'],
                'state' => ['required', 'string', "in:{$oauth->getState()}"]
            ]);

            if ($request->has('error')) {
                throw new OAuthException(
                    Str::headline(
                        sprintf(
                            '%s: %s',
                            $request->get('error'),
                            $request->get('error_description')
                        )
                    )
                );
            }

            $accessToken = $provider->getAccessToken('authorization_code', $request->only('code'));
            $identity->getConfig()->setAccessToken((string)$accessToken->getToken());

            //Iterate tenants
            $tenants = array();

            foreach ($identity->getConnections() as $c) {
                $tenants[] = [
                    "Id" => $c->getTenantId(),
                    "Name" => $c->getTenantName(),
                    "ConnectionId" => $c->getId(),
                ];
            }

            //Store Token and Tenants
            $oauth->store($accessToken, $tenants);

            Event::dispatch(new XeroAuthorized($oauth->getData()));

            return $this->onSuccess();
        } catch (\throwable $e) {
            return $this->onFailure($e);
        }
    }

    public function onSuccess()
    {
        return Redirect::route(config('xero.oauth.redirect_on_success'));
    }

    public function onFailure(\throwable $e)
    {
        throw $e;
    }
}
