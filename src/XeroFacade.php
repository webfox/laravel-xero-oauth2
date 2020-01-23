<?php

namespace Webfox\Xero;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Webfox\Xero\XeroClass
 */
class XeroFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'xero';
    }
}
