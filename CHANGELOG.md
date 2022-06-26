# Changelog

All notable changes to `laravel-xero-oauth2` will be documented in this file

## v4.0.0 Release
- Support multiple tenants on one connection #71

> **Note**
> Unless you are using a custom OauthCredentialStore then this should be an in-place update, however you may be required to
> wipe and re-enable your credential storage.


## v3.0.0 Release
- Change FileStore to use FilesystemManager with disk configuration #67
- Drop Laravel 5 support
- Add PHP 8.1 support

## v2.0.7 Release
- Resolve redirect_uri being set to true when full url is used #59

## v2.0.6 Release
- Add grant type to refresh token method https://github.com/XeroAPI/xero-php-oauth2/issues/196

## v2.0.5 Release
- Moved the refreshing of the token to the resolution of the CredentialManager.
  This should resolve any issues where the Xero configuration object is never resolve (e.g. not using the AccountingApi class) #48
  
## v2.0.4 Release
- PHP 8 Support #44

## v2.0.3 Release
- Throw exception if unable to write token to file #40

## v2.0.2 Release
- Change Accounting Api binding from a singleton to a standard binding

## v2.0.1 Release
- Laravel 8 Support #33

## v2.0.0 Release
- Make most resolution of credentials a binding instead of a singleton to fix queued jobs #27
- Switch OauthCredentialProvider to be an interface to allow for custom storage #8
- Make old OauthCredentialProvider `\Webfox\Xero\Oauth2CredentialManagers\CacheStore`
- Added new `Webfox\Xero\Oauth2CredentialManagers\FileStore` as default
- Added ability to skip `route` helper for redirect_uri #10

## v1.3.1 Release

- Fix bug in Webhook.php #25

## v1.3.0 Release

- Remove PHP 7.4 typehint from webhook #17
- Upgrade to v2 of the Xero API #16 

## v1.2.1 Release

- Specify scope separator in Oauth provider #15

## v1.2.0 Release

- Fix issue of credentials being forgotten #11 #10 #3
- Updates to the readme
- Add accounting.settings as a default scope #12

## v 1.1.2

- Added support for Laravel 7

## v1.1.1

- Remove PHP 7.4 features to maintain 7.3 compatibility

## V1.1.0 Release

- Added webhook support

## v1.0.0 Release

- initial release
