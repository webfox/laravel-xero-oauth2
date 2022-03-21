# A Laravel integration for Xero using the Oauth 2.0 spec

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webfox/laravel-xero-oauth2.svg?style=flat-square)](https://packagist.org/packages/webfox/laravel-xero-oauth2)
[![Total Downloads](https://img.shields.io/packagist/dt/webfox/laravel-xero-oauth2.svg?style=flat-square)](https://packagist.org/packages/webfox/laravel-xero-oauth2)

This package integrates the new recommended package of [xeroapi/xero-php-oauth2](https://github.com/XeroAPI/xero-php-oauth2) using the Oauth 2.0 spec with
Laravel.

## Installation

You can install this package via composer using the following command:
```
composer require webfox/laravel-xero-oauth2
```

The package will automatically register itself.

You should add your Xero keys to your `.env` file using the following keys:
```
XERO_CLIENT_ID=
XERO_CLIENT_SECRET=
```

(on [Xero developer portal](https://developer.xero.com/app/manage)): ***IMPORTANT*** When setting up the application in Xero ensure your redirect url is:
```
https://{your-domain}/xero/auth/callback
```
*(The flow is xero/auth/callback performs the oAuth handshake and stores your token, then redirects you over to your success callback)*

You can publish the configuration file with:
```
php artisan vendor:publish --provider="Webfox\Xero\XeroServiceProvider" --tag="config"
```

## Scopes

You'll want to set the scopes required for your application in the config file.

The default set of scopes are  `openid`, `email`, `profile`, `offline_access`, and `accounting.settings`.
You can see all available scopes on [the official Xero documentation](https://developer.xero.com/documentation/oauth2/scopes).

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

## Credential Storage
Credentials are stored in a JSON file using the default disk on the Laravel Filesystem, with visibility set to private. This allows credential sharing across multiple servers using a shared disk such as S3, regardless of which server conducted the OAuth flow.

To use a different disk, change the `xero.credential_disk` config item to another disk defined in `config/filesystem.php`.

You can switch out the credential store (e.g. for your own `UserStore` if you wanted to store 
the credentials against your user) in one of two ways:

1. If it's a simple store and Laravel can automatically resolve your bindings, simply change the `xero.credential_store` config
key to point to your new implementation.
2. If it requires more advanced logic (e.g. using the current user to retrieve the credentials) then you can rebind this 
in your `AppServiceProvider` or a Middleware
e.g.

```php
$this->app->bind(OauthCredentialManager::class, function(Application $app) {
    return new UserStorageProvider(
        \Auth::user(), // Storage Mechanism 
        $app->make('session.store'), // Used for storing/retrieving oauth 2 "state" for redirects
        $app->make(\Webfox\Xero\Oauth2Provider::class) // Used for getting redirect url and refreshing token
    );
});
``` 

An example UserStorageProvider [can been found here](https://github.com/webfox/laravel-xero-oauth2/issues/45#issuecomment-757552563)

## Using Webhooks
On your application in the Xero developer portal create a webhook to get your webhook key.

You can then add this to your `.env` file as

```
XERO_WEBHOOK_KEY=...
```

You can then setup a controller to handle your webhook and inject `\Webfox\Xero\Webhook` e.g.

```php
<?php

namespace App\Http\Controllers;

use Webfox\Xero\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use XeroApi\XeroPHP\Models\Accounting\Contact;
use XeroApi\XeroPHP\Models\Accounting\Invoice;

class XeroWebhookController extends Controller
{
    public function __invoke(Request $request, Webhook $webhook)
    {

        // The following lines are required for Xero's 'itent to receive' validation
        if (!$webhook->validate($request->header('x-xero-signature'))) {
            // We can't use abort here, since Xero expects no response body
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        // A single webhook trigger can contain multiple events, so we must loop over them
        foreach ($webhook->getEvents() as $event) {
            if ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'INVOICE') {
                $this->invoiceCreated($request, $event->getResource());
            } elseif ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'CONTACT') {
                $this->contactCreated($request, $event->getResource());
            } elseif ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'INVOICE') {
                $this->invoiceUpdated($request, $event->getResource());
            } elseif ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'CONTACT') {
                $this->contactUpdated($request, $event->getResource());
            }
        }

        return response('', Response::HTTP_OK);
    }

    protected function invoiceCreated(Request $request, Invoice $invoice)
    {
    }

    protected function contactCreated(Request $request, Contact $contact)
    {
    }

    protected function invoiceUpdated(Request $request, Invoice $invoice)
    {
    }

    protected function contactUpdated(Request $request, Contact $contact)
    {
    }

}
```

## Example calls

This package is simply a bridge so you don't have to deal with the Oauth2 gymnastics in Laravel.

Once you've have an instance of \XeroAPI\XeroPHP\Api\AccountingApi::class you're dealing directly with Xero's api library.

The XeroAPI PHP Oauth2 App repository has this list of examples of implementing calls to the API: e.g. invoice creation etc.

https://github.com/XeroAPI/xero-php-oauth2-app/blob/master/example.php

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
