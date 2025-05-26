<?php
namespace Kernel;

class RouteDefinition
{
    private $middlewares = [];
    private $callable;
    private $route;
    private $method;

    public function __construct(string $method, string $route, array $callable)
    {
        $this->method = $method;
        $this->route = $route;
        $this->callable = $callable;
    }

    public function middleware(...$args)
    {
        foreach ($args as $arg) {
            $this->middlewares[] = $arg;
        }
        return $this;
    }

    private function generateDynamicRoute(string $route): string
    {
        $pattern = '/\{([a-zA-Z0-9_]+)\}/';

        return preg_replace_callback($pattern, function ($matches) {
            return '(?P<' . $matches[1] . '>\w+)';
        }, $route);
    }

    public function make()
    {
        $route = $this->generateDynamicRoute($this->route);

        add_action('rest_api_init', function () use ($route) {
            register_rest_route('dnp/v1', "/{$route}", [
                'methods' => $this->method,
                'callback' => function ($request) {
                    $args = $request->get_params();
                    return (new Pipeline())->call($request, [
                        'middlewares' => $this->middlewares,
                        'callable' => $this->callable,
                        'route' => $this->route,
                        'method' => $this->method,
                    ], $args);
                },
                'permission_callback' => '__return_true',
            ]);
        });
    }
}
