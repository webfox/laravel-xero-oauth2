<?php

namespace Webfox\Xero;

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

        $this->app->singleton(Oauth2Provider::class, function (Application $app) {
            return new Oauth2Provider([
                'clientId'                => config('xero.oauth.client_id'),
                'clientSecret'            => config('xero.oauth.client_secret'),
                'redirectUri'             => route(config('xero.oauth.redirect_uri')),
                'urlAuthorize'            => config('xero.oauth.url_authorize'),
                'urlAccessToken'          => config('xero.oauth.url_access_token'),
                'urlResourceOwnerDetails' => config('xero.oauth.url_resource_owner_details'),
            ]);
        });

        $this->app->singleton(Configuration::class, function (Application $app, OauthCredentialManager $credentials) {
            $config = Configuration::getDefaultConfiguration();
            $config->setHost('https://api.xero.com');

            if ($credentials->exists()) {
                //expires
                //token
                //refresh_token
                //tenant_id
                //id_token
                if ($credentials->isExpired()) {
                    $credentials->refresh();
                }

                $config->setAccessToken($credentials->getAccessToken());
            }

            return $config;

        });

        $this->app->singleton(IdentityApi::class, function (Application $app, Configuration $xeroConfig) {
            return new IdentityApi(new GuzzleClient(), $xeroConfig);
        });

        $this->app->singleton(AccountingApi::class, function (Application $app, Configuration $xeroConfig) {
        });
    }
}
