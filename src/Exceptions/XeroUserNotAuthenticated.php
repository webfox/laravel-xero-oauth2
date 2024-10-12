<?php

namespace Webfox\Xero\Exceptions;

class XeroUserNotAuthenticated extends XeroException
{
    public static function make(): self
    {
        return new static('User is not authenticated');
    }
}
