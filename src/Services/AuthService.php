<?php
namespace Donapp\Services;

use Donapp\Models\User;

class AuthService {
    public function currentUser() {
        return (new User)->find(3);
    }
}
