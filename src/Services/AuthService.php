<?php
namespace App\Services;

class AuthService {
    public $user;
    public function __construct()
    {
        $this->user = wp_get_current_user();
    }
    public function currentUser() {
        return $this->user;
    }
}
