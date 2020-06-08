<?php


namespace Webfox\Xero;


use League\OAuth2\Client\Provider\GenericProvider;

class Oauth2Provider extends GenericProvider
{
    protected function getScopeSeparator()
    {
        return ' ';
    }
}
