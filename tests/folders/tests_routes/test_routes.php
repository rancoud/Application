<?php

declare(strict_types=1);

use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Stream;

$config = [
    'routes' => [
        [
            'methods'     => ['GET'],
            'url'         => '/no_handle',
            'callback'    => static function ($a, $b): void {
                $b($a);
            },
            'name'        => 'test_no_handle'
        ],
        [
            'methods'     => ['GET'],
            'url'         => '/',
            'callback'    => static function () {
                return (new Factory())->createResponse()->withBody(Stream::create('home'));
            },
            'name'        => 'test_home'
        ]
    ]
];

// @var \Rancoud\Router\Router $router
$router->setupRouterAndRoutesWithConfigArray($config);
