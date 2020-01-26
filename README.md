# A Laravel integration for Xero using the Oauth 2.0 spec

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webfox/laravel-xero-oauth2.svg?style=flat-square)](https://packagist.org/packages/webfox/laravel-xero-oauth2)
[![Total Downloads](https://img.shields.io/packagist/dt/webfox/laravel-xero-oauth2.svg?style=flat-square)](https://packagist.org/packages/webfox/laravel-xero-oauth2)

This package integrates the new reccomended package of [xeroapi/xero-php-oauth2](https://github.com/XeroAPI/xero-php-oauth2) using the Oauth 2.0 spec with
Laravel.

## Installation

You can install this package via composer using the following command:
```
composer require webfox/laravel-xero-oauth2
```

The package will automatically register itself.

You can publish the configuration file with:
```
php artisan vendor:publish --provider="Webfox\Xero\XeroServiceProvider" --tag="config"
```

You'll want to set the scopes required for your application in the config file.

You should add your Xero keys to your .env file using the following keys:
```
XERO_CLIENT_ID=
XERO_CLIENT_SECRET=
```

## Using the Package

This package registers two bindings into the service container you'll be interested in:
* `\XeroAPI\XeroPHP\Api\AccountingApi::class` this is the main api for Xero - see the [xeroapi/xero-php-oauth2 docs](https://github.com/XeroAPI/xero-php-oauth2/tree/master/docs) for usage.
  When you first resolve this dependency if the stored credentials are expired it will automatically refresh the token.
* `Webfox\Xero\OauthCredentialManager` this is the credential manager - The Accounting API requires we pass through a tenant ID on each request, this class is how you'd access that. 
  This is also where we can get information about the authenticating user. See below for an example.

*app\Http\Controllers\XeroController.php*
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Webfox\Xero\OauthCredentialManager;

class XeroController extends Controller
{

    public function index(Request $request, OauthCredentialManager $xeroCredentials)
    {
        try {
            // Check if we've got any stored credentials
            if ($xeroCredentials->exists()) {
                /* 
                 * We have stored credentials so we can resolve the AccountingApi, 
                 * If we were sure we already had some stored credentials then we could just resolve this through the controller
                 * But since we use this route for the initial authentication we cannot be sure!
                 */
                $xero             = resolve(\XeroAPI\XeroPHP\Api\AccountingApi::class);
                $organisationName = $xero->getOrganisations($xeroCredentials->getTenantId())->getOrganisations()[0]->getName();
                $user             = $xeroCredentials->getUser();
                $username         = "{$user['given_name']} {$user['family_name']} ({$user['username']})";
            }
        } catch (\throwable $e) {
            // This can happen if the credentials have been revoked or there is an error with the organisation (e.g. it's expired)
            $error = $e->getMessage();
        }

        return view('xero', [
            'connected'        => $xeroCredentials->exists(),
            'error'            => $error ?? null,
            'organisationName' => $organisationName ?? null,
            'username'         => $username ?? null
        ]);
    }

}
```

*resources\views\xero.blade.php*
```
@extends('_layouts.main')

@section('content')        
@if($error)
    <h1>Your connection to Xero failed</h1>
    <p>{{ $error }}</p>
    <a href="{{ route('xero.auth.authorize') }}" class="btn btn-primary btn-large mt-4">
        Reconnect to Xero
    </a>
@elseif($connected)
    <h1>You are connected to Xero</h1>
    <p>{{ $organisationName }} via {{ $username }}</p>
    <a href="{{ route('xero.auth.authorize') }}" class="btn btn-primary btn-large mt-4">
        Reconnect to Xero
    </a>
@else
    <h1>You are not connected to Xero</h1>
    <a href="{{ route('xero.auth.authorize') }}" class="btn btn-primary btn-large mt-4">
        Connect to Xero
    </a>
@endif
@endsection
```

*routes/web.php*
```php
/* 
 * We name this route xero.auth.success as by default the config looks for a route with this name to redirect back to
 * after authentication has succeeded. The name of this route can be changed in the config file.
 */
Route::get('/manage/xero', [\App\Http\Controllers\XeroController::class, 'index'])->name('xero.auth.success');
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
