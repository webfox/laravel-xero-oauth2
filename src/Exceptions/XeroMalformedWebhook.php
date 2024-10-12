<?php

namespace Webfox\Xero\Exceptions;

class XeroMalformedWebhook extends XeroException
{
    public static function make(): self
    {
        return new static('The webhook payload was malformed');
    }
}
