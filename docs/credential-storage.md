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

An example UserStorageProvider [can be found here](https://github.com/webfox/laravel-xero-oauth2/issues/45#issuecomment-757552563)