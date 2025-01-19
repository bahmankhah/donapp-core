<?php

namespace Kernel\Facades;

use Kernel\Application;

class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Application::class;
    }
}