<?php

namespace Kernel\Auth;

use Kernel\Adapters\AdapterManager;


class AuthManager extends AdapterManager
{

    public function getKey(): string{
        return 'auth';
    }

}
