<?php

namespace Webfox\Xero\Exceptions;

class XeroFailedToWriteFile extends XeroException
{
    public static function make(string $filePath): self
    {
        return new static('Failed to write file: ' . $filePath);
    }
}