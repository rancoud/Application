# Application Package

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rancoud/application)
[![Packagist Version](https://img.shields.io/packagist/v/rancoud/application)](https://packagist.org/packages/rancoud/application)
[![Packagist Downloads](https://img.shields.io/packagist/dt/rancoud/application)](https://packagist.org/packages/rancoud/application)
[![Composer dependencies](https://img.shields.io/badge/dependencies-0-brightgreen)](https://github.com/rancoud/application/blob/master/composer.json)
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
```php
$folders = [
    'ROOT' => folder_path,
    'ROUTES' => folder_path
];
// ROOT is used for reading the .env file
// ROUTES is used for initialize the router with routes

$app = new Application($folders);

$request = (new ServerRequestFactory())->createServerRequest($method, $path);

$response = $app->run($request);
```

You can add more folders in constructor  

```php
$folders = [
    'ROOT' => folder_path,
    'ROUTES' => folder_path,
    'EXTRA' => folder_path
];

$app = new Application($folders);

// you can access to specific folders
$folderExtra = Application::getFolder('EXTRA');
```

### Environment File  
You can specify another environment file instead of using .env in ROOT folder  

```php
$env = new Environment([folder_path], 'another.env');
$app = new Application($folders, $env);

// you can access to the environment
$config = Application::getConfig();
```

### Routes  
By default it will load all php files in the ROUTES folder.  
You can specify in .env file what routes file you want to use.  
```dotenv
; it will require() 3 files: www.php , backoffice.php and api.php in the routes folder
ROUTES=www,backoffice,api
```

This is an example how to add routes for the router  

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
// if enable, all saved queries will be display in $infos['database']
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
; valid timezones are checked with DateTimeZone::listIdentifiers()
TIMEZONE="Europe/Paris"
```

### Debug Infos
You have to enable it in .env file
```dotenv
; for enabling the rest of debug parameters
DEBUG=true

; get request object
DEBUG_REQUEST=true

; get response object
DEBUG_RESPONSE=true

; get all save queries
DEBUG_DATABASE=true

; get all values in session
DEBUG_SESSION=true

; get memory usage, limit and percentage
DEBUG_MEMORY=true

; get time spend between new App and the call of function getDebugInfos
DEBUG_SPEED=true

; get all included files
DEBUG_INCLUDED_FILES=true
```

### Bags
You can put whatever you want in "bags", like your own database driver
```php
$app = new Application($folders, $env);

Application::setInBag('db', $mydb);
$dbInBag = Application::getInBag('db');
Application::removeInBag('db');
```

## Application Constructor
### Settings
#### Mandatory
| Parameter | Type | Description |
| --- | --- | --- |
| folders | array | Folder's list. ROOT and ROUTES are mandatory |

#### Optionnals
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
* getInBag(name: string): mixed  
* removeInBag(name: string): void  
* setInBag(name: string, object: mixed): void  

## Optionnals Dependencies
[Database package](https://github.com/rancoud/Database)  
[Session package](https://github.com/rancoud/Session)  

## How to Dev
`composer ci` for php-cs-fixer and phpunit and coverage  
`composer lint` for php-cs-fixer  
`composer test` for phpunit and coverage  