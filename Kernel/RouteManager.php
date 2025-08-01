<?php
namespace Kernel;

class RouteManager
{
    public $names = [];
    public function setName(RouteDefinition $route, string $name): void
    {
        if (isset($this->names[$name])) {
            throw new \Exception("Route name '{$name}' already exists.");
        }
        $this->names[$name] = $route;
    }
    public function getName(string $name): ?RouteDefinition
    {
        if (isset($this->names[$name])) {
            return $this->names[$name];
        }
        return null;
    }

    public function get(string $route, array $callable): RouteDefinition
    {
        return new RouteDefinition('GET', $route, $callable);
    }

    public function post(string $route, array $callable): RouteDefinition
    {
        return new RouteDefinition('POST', $route, $callable);
    }

}
