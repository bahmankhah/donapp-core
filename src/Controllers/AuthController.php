<?php
namespace Donapp\Controllers;

use Kernel\Container;

class AuthController {
    private $authService;

    public function __construct() {
        $this->authService = Container::resolve('AuthService');
    }

    public function checkAuth($request){  
        return res(true);
    }
}
