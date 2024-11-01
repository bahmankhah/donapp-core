<?php
namespace Donapp\Routes;

use Donapp\Controllers\AuthController;

class RouteServiceProvider {
    public function register() {
        echo is_user_logged_in();

        add_action('rest_api_init', function () {
            register_rest_route('donapp/v1', '/auth-check', [
                'methods' => 'GET',
                'callback' => [(new AuthController()), 'checkAuth'],
                'permission_callback' => [$this, 'permissionCheck'],
            ]);
        });
    }

    public function permissionCheck(){
        return is_user_logged_in();
    }
}
