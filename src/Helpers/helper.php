<?php
if(!function_exists('res')){
    function res($data): WP_REST_Response{
        return rest_ensure_response($data);
    }
}