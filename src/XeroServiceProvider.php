<?php

namespace Webfox\Xero;

use Illuminate\Http\Request;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Foundation\Application;
use XeroAPI\XeroPHP\Api\AccountingApi;
use Illuminate\Support\ServiceProvider;

class XeroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('xero.php'),
            ], 'config');

        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'xero');

        /*
         * Singleton as this is how the package talks to Xero,
         * there's no reason for this to need to change
         */
        $this->app->singleton(Oauth2Provider::class, function (Application $app) {
            return new Oauth2Provider([
                'clientId'                => config('xero.oauth.client_id'),
                'clientSecret'            => config('xero.oauth.client_secret'),
                'redirectUri'             => config('xero.oauth.redirect_full_url')
                    ? config('xero.oauth.redirect_uri')
                    : route(config('xero.oauth.redirect_uri')),
                'urlAuthorize'            => config('xero.oauth.url_authorize'),
                'urlAccessToken'          => config('xero.oauth.url_access_token'),
                'urlResourceOwnerDetails' => config('xero.oauth.url_resource_owner_details'),
            ]);
        });

        $this->app->bind(OauthCredentialManager::class, function(Application  $app) {
            $credentials = $app->make(config('xero.credential_store'));

            if ($credentials->exists() && $credentials->isExpired()) {
                $credentials->refresh();
            }

            return $credentials;
        });

        $this->app->bind(Configuration::class, function (Application $app) {
            $credentials = $app->make(OauthCredentialManager::class);
            $config = new Configuration();
            $config->setHost(config('xero.api_host'));

            if ($credentials->exists()) {
                $config->setAccessToken($credentials->getAccessToken());
            }

            return $config;

        });

        $this->app->bind(IdentityApi::class, function (Application $app) {
            return new IdentityApi(new GuzzleClient(), $app->make(Configuration::class));
        });

        $this->app->bind(AccountingApi::class, function (Application $app) {
            return new AccountingApi(new GuzzleClient(), $app->make(Configuration::class));
        });

        $this->app->bind(Webhook::class, function(Application $app) {
            return new Webhook(
                $app->make(OauthCredentialManager::class),
                $app->make(AccountingApi::class),
                $this->app->make(Request::class)->getContent(),
                config('xero.oauth.webhook_signing_key')
            );
        });
    }
}
