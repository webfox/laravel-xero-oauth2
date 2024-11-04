<?php

namespace Webfox\Xero;

use Illuminate\Database\Eloquent\Model;

class Xero
{
    public static ?Model $modelStorage = null;

    public static string $modelAttribute = 'xero_credentials';

    public static string $defaultAuthGuard = 'web';

    public static function useModelStorage(Model $model): void
    {
        static::$modelStorage = $model;
    }

    public static function useAttributeOnModelStore(string $attribute): void
    {
        static::$modelAttribute = $attribute;
    }

    public static function getModelStorage(): ?Model
    {
        return static::$modelStorage;
    }

    public static function getModelAttribute(): string
    {
        return static::$modelAttribute;
    }

    public static function getDefaultAuthGuard(): string
    {
        return static::$defaultAuthGuard;
    }

    public static function setDefaultAuthGuard(string $guard): void
    {
        static::$defaultAuthGuard = $guard;
    }
}
