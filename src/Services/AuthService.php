<?php
namespace Donapp\Services;

use Donapp\Models\User;

class AuthService {
    public function checkIfUserLoggedIn() {
        echo function_exists('is_user_logged_in');
        return is_user_logged_in();
    }
}
