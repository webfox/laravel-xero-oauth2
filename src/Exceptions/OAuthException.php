<?php

namespace Webfox\Xero\Exceptions;

use Illuminate\Support\Str;

class OAuthException extends XeroException
{
    public static function make(string $error, string $errorDescription): self
    {
        return new static(Str::headline(
            sprintf(
                '%s: %s',
                $error,
                $errorDescription
            )
        ));
    }
}
