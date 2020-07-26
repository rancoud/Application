<?php

declare(strict_types=1);

$config = [
    'router' => [
        'default_404' => false
    ],
    'routes' => [
        [
            'methods'     => ['GET'],
            'url'         => '/no_handle',
            'callback'    => function($a,$b){$b($a);},
            'name'        => 'test_no_handle'
        ]
    ]
];

/* @var \Rancoud\Router\Router $router */
$router->setupRouterAndRoutesWithConfigArray($config);