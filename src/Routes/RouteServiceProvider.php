<?php
namespace Donapp\Routes;

use Donapp\Controllers\AuthController;

class RouteServiceProvider {
    public function register() {
        add_action('rest_api_init', function () {
            register_rest_route('donapp/v1', '/auth-check', [
                'methods' => 'GET',
                'callback' => [(new AuthController()), 'checkAuth'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function permissionCheck(){
        echo is_user_logged_in();

        return is_user_logged_in();
    }
}
