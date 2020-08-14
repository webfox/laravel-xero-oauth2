# Changelog

All notable changes to `laravel-xero-oauth2` will be documented in this file

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
