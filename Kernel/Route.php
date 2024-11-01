<?php
namespace Kernel;

class Route{
    private $params = [
        'middlewares'=>[],
        'callable'=>null,
        'route'=>'',
        'method'=>null,
    ];
    public function get($route,array $callable){
        $this->params['callable'] = $callable;
        $this->params['method']= 'GET';
        $this->params['route'] = $route;
        return $this;
    }
    public function post($route, array $callable){
        $this->params['callable'] = $callable;
        $this->params['method']= 'POST';
        $this->params['route'] = $route;
        return $this;
    }
    public function middleware(...$args){
        foreach($args as $arg){
            $this->params['middlewares'][] = $arg;
        }
        return $this;
    }

    public function make(){
        $params = $this->params;

        add_action('rest_api_init', function () use ($params) {
            register_rest_route('dnp/v1', "/{$params['route']}", [
                'methods' => $this->params['method'],
                'callback' => function($request) use ($params) {
                    return (new Pipeline())->call($request, $params);
                },
                'permission_callback' => '__return_true',
            ]);
        });
    }

}