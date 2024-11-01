<?php
namespace Donapp\Services;

use Donapp\Models\User;

class AuthService {
    public function checkIfUserLoggedIn() {
        return is_user_logged_in();
    }
}
