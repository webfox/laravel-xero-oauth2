## Using the Package

This package registers two bindings into the service container you'll be interested in:

* `\XeroAPI\XeroPHP\Api\AccountingApi::class` this is the main api for Xero - see the [xeroapi/xero-php-oauth2 docs](https://github.com/XeroAPI/xero-php-oauth2/tree/master/docs) for usage.
  When you first resolve this dependency if the stored credentials are expired it will automatically refresh the token.
* `Webfox\Xero\OauthCredentialManager` this is the credential manager - The Accounting API requires we pass through a tenant ID on each request, this class is how you'd access that.
  This is also where we can get information about the authenticating user. See below for an example.

## Scopes

You'll want to set the scopes required for your application in the config file.

The default set of scopes are  `openid`, `email`, `profile`, `offline_access`, and `accounting.settings`.
You can see all available scopes on [the official Xero documentation](https://developer.xero.com/documentation/oauth2/scopes).

## Example calls

This package is simply a bridge so you don't have to deal with the Oauth2 gymnastics in Laravel.

Once you've an instance of `\XeroAPI\XeroPHP\Api\AccountingApi::class` you're dealing directly with Xero's api library.

The XeroAPI PHP Oauth2 App repository has this list of examples of implementing calls to the API: e.g. invoice creation etc.

<https://github.com/XeroAPI/xero-php-oauth2-app/blob/master/example.php>
