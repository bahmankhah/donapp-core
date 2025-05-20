<?php

namespace Kernel\Facades;

use Kernel\Application;
use Kernel\Auth\AuthManager;

/**
 * @method static \Kernel\Application make($class, array $params = [])
 * @see \Kernel\Application
**/
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuthManager::class;
    }
}