<?php

namespace Webfox\Xero\Exceptions;

class XeroFailedToDecodeWebhookEvent extends XeroException
{
    public static function make(): self
    {
        return new static('The webhook payload could not be decoded: '.json_last_error_msg());
    }
}