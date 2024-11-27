<?php

namespace Webfox\Xero\Exceptions;

class XeroMalformedWebhookEvent extends XeroException
{
    public static function make(): self
    {
        return new static('The event payload was malformed; missing required field');
    }
}
