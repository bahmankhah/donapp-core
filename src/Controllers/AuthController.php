<?php
namespace Donapp\Controllers;

use Donapp\Container;

class AuthController {
    private $authService;

    public function __construct() {
        $this->authService = Container::resolve('AuthService');
    }

    public function checkAuth($request) {
        return rest_ensure_response([
            'status' => 'success',
            'request'=>$request,
            // 'message' => 'User is logged in.',
            'user' => $this->authService->currentUser(),
        ]);
    }
}
