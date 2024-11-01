<?php
namespace Donapp\Controllers;

use Donapp\Container;

class AuthController {
    private $authService;

    public function __construct() {
        $this->authService = Container::resolve('AuthService');
    }

    public function checkAuth($request) {
        $is_logged_in = $this->authService->checkIfUserLoggedIn();
        
        if ($is_logged_in) {
            // $user = $this->authService->getCurrentUser();
            return rest_ensure_response([
                'status' => 'success',
                'message' => 'User is logged in.',
                // 'user' => $user->getData()
            ]);
        } else {
            return rest_ensure_response([
                'status' => 'error',
                'message' => 'User is not logged in.'
            ]);
        }
    }
}
