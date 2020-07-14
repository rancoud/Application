# Application Package

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rancoud/application)
[![Packagist Version](https://img.shields.io/packagist/v/rancoud/application)](https://packagist.org/packages/rancoud/application)
[![Packagist Downloads](https://img.shields.io/packagist/dt/rancoud/application)](https://packagist.org/packages/rancoud/application)
[![Composer dependencies](https://img.shields.io/badge/dependencies-2-brightgreen)](https://github.com/rancoud/application/blob/master/composer.json)
[![Test workflow](https://img.shields.io/github/workflow/status/rancoud/application/test?label=test&logo=github)](https://github.com/rancoud/application/actions?workflow=test)
[![Codecov](https://img.shields.io/codecov/c/github/rancoud/application?logo=codecov)](https://codecov.io/gh/rancoud/application)
[![composer.lock](https://poser.pugx.org/rancoud/application/composerlock)](https://packagist.org/packages/rancoud/application)

Application skeleton with strict minimum Router and Environment.  

## Dependencies
[Environment package](https://github.com/rancoud/Environment)  
[Router package](https://github.com/rancoud/Router)  

## Installation
```php
composer require rancoud/application
```

## How to use it?
### General
#### Requirements
You need `.env` file and route file called for example `routes.php`
Content of default `.env` file
```dotenv
# setup timezone, by default use timezone from php.ini (valid timezones are checked with DateTimeZone::listIdentifiers())
TIMEZONE=null

# file names that contain route configurations, if null it will load all files in current folder provided to the Application constructor
ROUTES=null

# to enable all DEBUG_* parameters
DEBUG=false

# enable error_reporting: show php errors
DEBUG_PHP=false

# save PSR-7 request object for Application->getDebugInfos()
DEBUG_REQUEST=false

# save PSR-7 response object for Application->getDebugInfos()
DEBUG_RESPONSE=false

# save queries for Application->getDebugInfos()
DEBUG_DATABASE=false

# add all values of Session object to Application->getDebugInfos()
DEBUG_SESSION=false

# add memory usage/limit/percentage to Application->getDebugInfos()
DEBUG_MEMORY=false

# save elapsed time of each call of Application->run() for Application->getDebugInfos()
DEBUG_RUN_ELAPSED_TIMES=false

# add all included files included to Application->getDebugInfos()
DEBUG_INCLUDED_FILES=false
```
`DEBUG_*` infos are available with `Application->getDebugInfos();` (except `DEBUG_PHP`)  
Content of `routes.php`
```php
<?php

use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Stream;

/** @var \Rancoud\Router\Router $router */
$router->any('/home', static function ($request, $next) {
    return (new Factory())->createResponse()->withBody(Stream::create('hello world'));
});
```
#### Usage
```php
// you have to set thoses required folders
$folders = [
    'ROOT' => 'folder/path/of/env',    // ROOT is used to read the .env file (must be a folder)
    'ROUTES' => 'folder/path/of/routes'   // ROUTES is used to initialize the router with routes (must be a folder, so it will read php files inside it)
];

$app = new Application($folders);

// create server request from globals
$request = (new \Rancoud\Http\Message\Factory\Factory())->createServerRequestFromGlobals();

// you can also create request from scratch for example
// $request = new \Rancoud\Http\Message\ServerRequest('GET', '/home');

// $response can be null if no route AND no default404 has been found by the Router
$response = $app->run($request);

// you can send response output directly (if not null of course)
$response->send();
```

You can add more folders in constructor  

```php
$folders = [
    'ROOT' => 'folder/path/of/env',
    'ROUTES' => 'folder/path/of/routes',
    'EXTRA' => 'folder/path/of/extra'
];

$app = new Application($folders);

// you can access to specific folders
$folderExtra = Application::getFolder('EXTRA');
```

### Environment File  
You can specify another environment file instead of using .env in ROOT folder  

```php
$env = new Environment('folder/path/of/env', 'another.env');
$app = new Application($folders, $env);

// you can access to the environment
$config = Application::getConfig();
```

### Routes  
By default it will load all php files in the ROUTES folder.  
You can specify in .env file which routes files you want to use.  
```dotenv
# in this example, it will require() 3 files: www.php , backoffice.php and api.php in the routes folder
ROUTES=www,backoffice,api
```

This example show how to add routes for the router  

```php
$config = [
    'routes' => [
        [
            'methods'     => ['GET'],
            'url'         => '/',
            'callback'    => function($a,$b){
                return (new MessageFactory())->createResponse(200, null, [], 'home');
            },
            'name'        => 'test_home'
        ]
    ]
];

/* @var \Rancoud\Router\Router $router */
$router->setupRouterAndRoutesWithConfigArray($config);
```

### Router  
```php
$app = new Application($folders, $env);

$router = Application::getRouter();
```

### Database  
You have to use this [Database package](https://github.com/rancoud/Database)  
```php
$app = new Application($folders, $env);

Application::setDatabase($database);
$db = Application::getDatabase();

$infos = $app->getDebugInfos();
// when enabled, all saved queries will be display in $infos['database']
```
You are free to use something else if you don't use functions `setDatabase` and `getDatabase`.  

### Session  
You can use this [Session package](https://github.com/rancoud/Session)  
It is used in the function `getDebugInfos()`  
You are free to use something else.  

### Timezone  
By default the timezone used will be from php.ini  
You can specify a timezone in .env file  
```dotenv
# valid timezones are checked with DateTimeZone::listIdentifiers()
TIMEZONE="Europe/Paris"
```

### Debug Infos
You have to enable it in .env file
```dotenv
# to enable all DEBUG_* parameters
DEBUG=true

# enable error_reporting: show php errors
DEBUG_PHP=true

# save PSR-7 request object for Application->getDebugInfos()
DEBUG_REQUEST=false

# save PSR-7 response object for Application->getDebugInfos()
DEBUG_RESPONSE=false

# save queries for Application->getDebugInfos()
DEBUG_DATABASE=false

# add all values of Session object to Application->getDebugInfos()
DEBUG_SESSION=false

# add memory usage/limit/percentage to Application->getDebugInfos()
DEBUG_MEMORY=false

# save elapsed time of each call of Application->run() for Application->getDebugInfos()
DEBUG_RUN_ELAPSED_TIMES=false

# add all included files included to Application->getDebugInfos()
DEBUG_INCLUDED_FILES=true
```

### Bags
You can put whatever you want in "bags", like your own database driver
```php
$app = new Application($folders, $env);

Application::setInBag('db', $mydb);
$dbInBag = Application::getFromBag('db');
Application::removeFromBag('db');
```

## Application Constructor
### Settings
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| folders | array | Folder's list. ROOT and ROUTES are mandatory |

#### Optionals
| Parameter | Type | Default value | Description |
| --- | --- | --- | --- |
| env | Rancoud\Environment\Environment | null | Setup a different .env file |

## Application Methods
### General Commands  
* run(request: \Psr\Http\Message\ServerRequestInterface): ?\Rancoud\Http\Message\Response  
* getDebugInfos(): array  

### Static Methods  
* getFolder(index: string): string  
* getInstance(): Rancoud\Application\Application  
* getConfig(): Rancoud\Environment\Environment  
* setDatabase(database: Rancoud\Database\Database): void  
* getDatabase(): ?Rancoud\Database\Database  
* getRouter(): Rancoud\Router\Router  
* getFromBag(name: string): mixed  
* removeFromBag(name: string): void  
* setInBag(name: string, object: mixed): void  

## Optionals Dependencies
[Database package](https://github.com/rancoud/Database)  
[Session package](https://github.com/rancoud/Session)  

## How to Dev
`composer ci` for php-cs-fixer and phpunit and coverage  
`composer lint` for php-cs-fixer  
`composer test` for phpunit and coverage  