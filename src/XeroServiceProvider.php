<?php

namespace Webfox\Xero;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Webfox\Xero\Clients\AccountAPIClient;
use Webfox\Xero\Clients\IdentityAPIClient;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Api\IdentityApi;
use XeroAPI\XeroPHP\Configuration;

class XeroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('xero.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'xero');

        $this->app->singleton(Xero::class, function (Application $app) {
            return new Xero();
        });

        /*
         * Singleton as this is how the package talks to Xero,
         * there's no reason for this to need to change
         */
        $this->app->singleton(Oauth2Provider::class, function (Application $app) {
            return new Oauth2Provider([
                'clientId' => config('xero.oauth.client_id'),
                'clientSecret' => config('xero.oauth.client_secret'),
                'redirectUri' => config('xero.oauth.redirect_full_url')
                    ? config('xero.oauth.redirect_uri')
                    : route(config('xero.oauth.redirect_uri')),
                'urlAuthorize' => config('xero.oauth.url_authorize'),
                'urlAccessToken' => config('xero.oauth.url_access_token'),
                'urlResourceOwnerDetails' => config('xero.oauth.url_resource_owner_details'),
            ]);
        });

        $this->app->bind(OauthCredentialManager::class, function (Application $app) {
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
            return new IdentityApi(IdentityAPIClient::getHttpClient(), $app->make(Configuration::class));
        });

        $this->app->bind(AccountingApi::class, function (Application $app) {
            return new AccountingApi(AccountAPIClient::getHttpClient(), $app->make(Configuration::class));
        });

        $this->app->bind(Webhook::class, function (Application $app) {
            return new Webhook(
                $app->make(OauthCredentialManager::class),
                $app->make(AccountingApi::class),
                $this->app->make(Request::class)->getContent(),
                config('xero.oauth.webhook_signing_key')
            );
        });
    }
}
