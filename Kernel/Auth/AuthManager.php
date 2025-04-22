<?php

namespace Kernel\Auth;

use Kernel\Adapters\AdapterManager;

use Src\Services\AuthService;

class AuthManager extends AdapterManager
{

    public function getKey(): string{
        return 'auth';
    }

    protected $guards = [];

    public function __construct()
    {
        $this->guards = $this->loadGuards();
    }

    /**
     * Load guards configuration.
     */
    protected function loadGuards()
    {
        // Load guard configuration (you can load from a config file here)
        return [
            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
            'api' => [
                'driver' => 'token',
                'provider' => 'users',
            ],
        ];
    }

    /**
     * Get the active guard.
     */
    public function guard($name = 'web')
    {
        if (isset($this->guards[$name])) {
            return $this->createGuard($this->guards[$name]);
        }

        throw new \Exception("Guard [$name] not found.");
    }

    /**
     * Create and return a guard instance.
     */
    protected function createGuard($config)
    {
        $driver = $config['driver'];

        // Add logic to return different guard types based on the driver.
        if ($driver === 'session') {
            // return new SessionGuard(new AuthService());
        } elseif ($driver === 'token') {
            // return new TokenGuard(new AuthService());
        }

        throw new \Exception("Driver [$driver] not supported.");
    }
}
