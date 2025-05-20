<?php

namespace Kernel;

class Route
{
    private $params = [
        'middlewares' => [],
        'callable' => null,
        'route' => '',
        'method' => null,
    ];
    public function get($route, array $callable)
    {
        $this->params['callable'] = $callable;
        $this->params['method'] = 'GET';
        $this->params['route'] = $route;
        return $this;
    }
    public function post($route, array $callable)
    {
        $this->params['callable'] = $callable;
        $this->params['method'] = 'POST';
        $this->params['route'] = $route;
        return $this;
    }
    public function middleware(...$args)
    {
        foreach ($args as $arg) {
            $this->params['middlewares'][] = $arg;
        }
        return $this;
    }

    private function generateDynamicRoute($route)
    {
        // Match placeholders like {something} in the route
        $pattern = '/\{([a-zA-Z0-9_]+)\}/';

        // Replace {something} with the corresponding regex group (?P<something>\w+)
        $route = preg_replace_callback($pattern, function ($matches) {
            // Convert {something} to (?P<someting>\w+)
            return '(?P<' . $matches[1] . '>\w+)';
        }, $route);

        // Add any necessary formatting (like leading slashes)
        return $route;
    }

    public function make()
    {
        $params = $this->params;
        $route = $this->generateDynamicRoute($params['route']);

        add_action('rest_api_init', function () use ($params, $route) {
            register_rest_route('dnp/v1', "/{$route}", [
                'methods' => $this->params['method'],
                'callback' => function ($request) use ($params) {
                    $args = $request->get_params();
                    return (new Pipeline())->call($request, $params, $args);
                },
                'permission_callback' => '__return_true',
            ]);
        });
    }
}
