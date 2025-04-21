<?php

declare(strict_types=1);

use tests\InvalidClass;

$config = [
    'router' => [
        'default_404' => InvalidClass::class
    ],
    'routes' => [
        [
            'methods'     => ['GET'],
            'url'         => '/no_handle',
            'callback'    => static function ($a, $b): void {
                $b($a);
            },
            'name'        => 'test_no_handle'
        ]
    ]
];

// @var \Rancoud\Router\Router $router
$router->setupRouterAndRoutesWithConfigArray($config);
