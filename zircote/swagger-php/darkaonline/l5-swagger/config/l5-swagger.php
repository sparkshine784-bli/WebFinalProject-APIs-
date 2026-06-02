<?php

return [
   
    'doc-dir' => storage_path() . '/api-docs',

    
    'doc-route' => 'docs',

    
    "app-dir" => "app",

   
    "excludes" => [],

    "generateAlways" => env('SWAGGER_GENERATE_ALWAYS', false),

    "api-key" => env('API_AUTH_TOKEN', false),

    
    "api-key-var" => env('API_KEY_VAR', 'api_key'),

    
    "api-key-inject" => env('API_KEY_INJECT', 'query'),

    "default-api-version" => env('DEFAULT_API_VERSION', '1'),

    "default-swagger-version" => env('SWAGGER_VERSION', '2.0'),

    
    "default-base-path" => "",

    "behind-reverse-proxy" => false,
    
   
];
