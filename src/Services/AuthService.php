<?php
namespace Donapp\Services;

class AuthService {
    public function currentUser() {
        
        return wp_get_current_user();
    }
}
