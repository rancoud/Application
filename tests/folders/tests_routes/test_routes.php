<?php

declare(strict_types=1);

use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Stream;

$config = [
    'routes' => [
        [
            'methods'     => ['GET'],
            'url'         => '/no_handle',
            'callback'    => function($a,$b){$b($a);},
            'name'        => 'test_no_handle'
        ],
        [
            'methods'     => ['GET'],
            'url'         => '/',
            'callback'    => function($a,$b){
                return (new Factory())->createResponse(200)->withBody(Stream::create('home'));
            },
            'name'        => 'test_home'
        ]
    ]
];

/* @var \Rancoud\Router\Router $router */
$router->setupRouterAndRoutesWithConfigArray($config);