<?php
namespace Kernel;

class Route{
    private $params = [
        'middlewares'=>[],
        'callable'=>null,
        'route'=>'',
        'method'=>'',
    ];
    public function get($route,array $callable){
        $params['callable'] = $callable;
        $params['method']= 'GET';
        $params['route'] = $route;
        return $this;
    }
    public function post(array $callable){
        $params['callable'] = $callable;
        $params['method']= 'GET';
        $params['route'] = $route;
        return $this;
    }
    public function middleware(...$args){
        foreach($args as $arg){
            $params['middlewares'][] = $arg;
        }
        return $this;
    }

    public function make(){
        $params = $this->params;
        add_action('rest_api_init', function () use ($params) {
            register_rest_route('core/v1', "/{$this->params['route']}", [
                'methods' => $this->params['method'],
                'callback' => function($request) use ($params) {
                    // Directly call handleUserDataRequest with custom_arg set manually
                     // Example value you want to pass
                    return (new Pipeline())->call($request, $params);
                },
                'permission_callback' => '__return_true',
            ]);
        });
    }

}