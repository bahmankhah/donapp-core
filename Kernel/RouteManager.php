<?php
namespace Kernel;

class RouteManager
{
    public function get(string $route, array $callable): RouteDefinition
    {
        return new RouteDefinition('GET', $route, $callable);
    }

    public function post(string $route, array $callable): RouteDefinition
    {
        return new RouteDefinition('POST', $route, $callable);
    }

}
