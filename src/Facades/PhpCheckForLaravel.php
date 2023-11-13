<?php

namespace Croustibat\PhpCheckForLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Croustibat\PhpCheckForLaravel\PhpCheckForLaravel
 */
class PhpCheckForLaravel extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Croustibat\PhpCheckForLaravel\PhpCheckForLaravel::class;
    }
}
