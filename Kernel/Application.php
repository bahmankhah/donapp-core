<?php

namespace Kernel;

use Exception;
use ReflectionClass;

class Application extends Container{
    /**
     * Resolve a class instance from the container.
     *
     * @param string $class
     * @param array $params
     *
     * @return object
     *
     * @throws Exception
     */
    public function make($class, array $params = [])
    {
        if (!class_exists($class)) {
            throw new Exception("Class {$class} not found.");
        }

        $reflection = new ReflectionClass($class);

        // Get the constructor
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class(); // No constructor, just instantiate
        }

        // Resolve the constructor's parameters
        $dependencies = $constructor->getParameters();
        $resolved = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $params)) {
                // Use provided parameter if available
                $resolved[] = $params[$dependency->getName()];
            } elseif ($dependency->getType() && !$dependency->getType()->isBuiltin()) {
                // Resolve non-built-in type dependencies
                $resolved[] = $this->resolve($dependency->getType()->getName());
            } elseif ($dependency->isDefaultValueAvailable()) {
                // Use the default value if available
                $resolved[] = $dependency->getDefaultValue();
            } else {
                throw new Exception("Unable to resolve dependency [{$dependency->getName()}] for class [{$class}].");
            }
        }
        
        return $reflection->newInstanceArgs($resolved);
    }

    public function setRequest($request){
        Container::bind('request', function() use ($request) {
            return $request;
        });
    }

    public function request(){
        return Container::resolve('request');
    }
}