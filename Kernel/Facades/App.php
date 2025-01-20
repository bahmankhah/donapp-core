<?php

namespace Kernel\Facades;

use Kernel\Application;

/**
 * @method static \Kernel\Application make($class, array $params = [])
 * @see \Kernel\Application
**/
class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Application::class;
    }
}