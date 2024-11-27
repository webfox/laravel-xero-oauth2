<?php

namespace Webfox\Xero\Exceptions;

class XeroTenantNotFound extends XeroException
{
    public static function make(): self
    {
        return new static('No such tenant exists');
    }
}
