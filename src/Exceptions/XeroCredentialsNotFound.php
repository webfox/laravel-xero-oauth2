<?php

namespace Webfox\Xero\Exceptions;

class XeroCredentialsNotFound extends XeroException
{
    public static function make(): self
    {
        return new static('Xero oauth credentials are missing');
    }
}
