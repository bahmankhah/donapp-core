<?php

namespace Kernel\Auth\Guards;

use Kernel\Adapters\Adapter;
use Kernel\Contracts\Auth\Guard;

class TokenGuard extends Adapter implements Guard{

    public function check(): bool
    {
        return true;
        // Implement the logic to check if the user is authenticated
        // Return true if authenticated, false otherwise
    }

    public function user()
    {
        // Implement the logic to get the currently authenticated user
        // Return the user object or null if not authenticated
    }

    public function login($user)
    {
        // Implement the logic to log the user into the application
    }

    public function logout()
    {
        // Implement the logic to log the user out of the application
    }


    public function attempt(string $identifier, string $password)
    {
        // Implement the logic to attempt to authenticate the user using the given credentials
        // Return true if authenticated, false otherwise
    }
}