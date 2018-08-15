# Application Package

[![Build Status](https://travis-ci.org/rancoud/Application.svg?branch=master)](https://travis-ci.org/rancoud/Application) [![Coverage Status](https://coveralls.io/repos/github/rancoud/Application/badge.svg?branch=master)](https://coveralls.io/github/rancoud/Application?branch=master)

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
* run(request: \Psr\Http\Message\ServerRequestInterface):?\Rancoud\Http\Message\Response  
* getDebugInfos():array  

### Static Methods  
* static getFolder(index: string):string  
* static getInstance():Rancoud\Application\Application  
* static getConfig():Rancoud\Environment\Environment  
* static setDatabase(database: Rancoud\Database\Database):void  
* static getDatabase():?Rancoud\Database\Database  
* static getRouter():Rancoud\Router\Router  
* static getInBag(name: string):mixed  
* static removeInBag(name: string):void  
* static setInBag(name: string, object: mixed):void  

## Optionnals Dependencies
[Database package](https://github.com/rancoud/Database)  
[Session package](https://github.com/rancoud/Session)  

## How to Dev
`./run_all_commands.sh` for php-cs-fixer and phpunit and coverage  
`./run_php_unit_coverage.sh` for phpunit and coverage  