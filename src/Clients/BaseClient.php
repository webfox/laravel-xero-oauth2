<?php

namespace Webfox\Xero\Clients;

use GuzzleHttp\Client;
use Mockery;

abstract class BaseClient
{
    public static ?Client $httpClient = null;

    public static function fake(): void
    {
        self::$httpClient = Mockery::mock(Client::class);
    }

    public static function getHttpClient(): Client|Mockery
    {
        if(self::$httpClient){
            return self::$httpClient;
        }

        return self::$httpClient = new Client();
    }
}