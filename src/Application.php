<?php

declare(strict_types=1);

namespace Rancoud\Application;

use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rancoud\Environment\Environment;
use Rancoud\Environment\EnvironmentException;
use Rancoud\Http\Message\Response;
use Rancoud\Router\Router;
use Rancoud\Router\RouterException;

/**
 * Class Application.
 */
class Application
{
    /** @var float */
    protected $appStart;
    /** @var array */
    protected $folders = [];
    /** @var Application */
    protected static $app = null;
    /** @var Router */
    protected $router = null;
    /** @var Environment */
    protected $config = [];
    /** @var bool */
    protected $isDebug = false;
    /** @var \Rancoud\Database\Database */
    protected $database = null;
    /** @var ServerRequestInterface */
    protected $request;
    /** @var ResponseInterface */
    protected $response;
    /** @var array */
    protected $bags = [];

    /**
     * App constructor.
     *
     * @param array            $folders
     * @param Environment|null $env
     *
     * @throws ApplicationException
     * @throws EnvironmentException
     */
    public function __construct(array $folders, Environment $env = null)
    {
        $this->appStart = microtime(true);

        $this->initFolders($folders);
        $this->initAttributes();
        $this->loadEnvironment($env);
        $this->setupApplication();
        $this->loadRoutes();
    }

    /**
     * @param array $folders
     *
     * @throws ApplicationException
     */
    protected function initFolders(array $folders): void
    {
        $props = $this->getFoldersName();
        $propsCount = count($props);
        $validProps = implode(', ', $props);
        foreach ($folders as $name => $folder) {
            if (!is_string($folder) || !file_exists($folder)) {
                throw new ApplicationException('"' . $name . '" is not a valid folder.');
            }

            $this->folders[$name] = $folder;
            if (in_array($name, $props, true)) {
                --$propsCount;
            }
        }

        if ($propsCount > 0) {
            throw new ApplicationException('Missing folder name. Use ' . $validProps);
        }
    }

    /**
     * @return array
     */
    protected function getFoldersName(): array
    {
        return ['ROOT', 'ROUTES'];
    }

    protected function initAttributes(): void
    {
        static::$app = $this;
        $this->router = new Router();
    }

    /**
     * @param Environment|null $env
     */
    protected function loadEnvironment(Environment $env = null): void
    {
        if ($env !== null) {
            $this->config = $env;
        } else {
            $this->config = new Environment($this->folders['ROOT']);
        }
    }

    /**
     * @throws EnvironmentException
     * @throws ApplicationException
     */
    protected function setupApplication(): void
    {
        $this->setupApplicationDebug();
        $this->setupTimezone();
    }

    /**
     * @throws EnvironmentException
     */
    protected function setupApplicationDebug(): void
    {
        if ($this->config->get('DEBUG') === true) {
            $this->isDebug = true;
            error_reporting(-1);
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
    }

    /**
     * @throws EnvironmentException
     * @throws ApplicationException
     */
    protected function setupTimezone(): void
    {
        $timezone = $this->config->get('TIMEZONE');
        if ($timezone === null) {
            return;
        }

        $allTimezones = DateTimeZone::listIdentifiers();
        if (in_array($timezone, $allTimezones, true)) {
            date_default_timezone_set($timezone);
        } else {
            $message = 'Invalid timezone: ' . $timezone . '. Check DateTimeZone::listIdentifiers()';
            throw new ApplicationException($message);
        }
    }

    /**
     * @throws ApplicationException
     * @throws EnvironmentException
     */
    protected function loadRoutes(): void
    {
        $routes = $this->config->get('ROUTES');
        if ($routes === null) {
            $this->loadAllRoutesFilesInRoutesFolder();

            return;
        }

        if (!is_string($routes)) {
            throw new ApplicationException('Invalid routes');
        }
        $routes = explode(',', $routes);

        foreach ($routes as $route) {
            if (!file_exists($this->folders['ROUTES'] . $route . '.php')) {
                throw new ApplicationException('Invalid route file');
            }

            $this->loadRouteFile($route . '.php');
        }
    }

    protected function loadAllRoutesFilesInRoutesFolder(): void
    {
        $res = opendir($this->folders['ROUTES']);
        while (($routeFile = readdir($res)) !== false) {
            if (mb_strripos($routeFile, '.php') === mb_strlen($routeFile) - 4) {
                $this->loadRouteFile($routeFile);
            }
        }
    }

    /**
     * @param $file
     */
    protected function loadRouteFile($file): void
    {
        $router = $this->router;

        require $this->folders['ROUTES'] . $file;

        $this->router = $router;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws InvalidArgumentException
     * @throws RouterException
     *
     * @return null|Response
     */
    public function run(ServerRequestInterface $request): ?Response
    {
        if ($this->router->findRouteRequest($request)) {
            /* @var Response $response */
            try {
                $response = $this->router->dispatch($request);
            } catch (RouterException $routerException) {
                if ($routerException->getMessage() === 'No route found to dispatch') {
                    return null;
                }
                throw $routerException;
            }
            $response = $response->withProtocolVersion($this->extractProtocolVersion($request));

            $this->request = $request;
            $this->response = $response;

            return $response;
        }

        return null;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    protected function extractProtocolVersion(ServerRequestInterface $request): string
    {
        $default = $request->getProtocolVersion();
        $serverParams = $request->getServerParams();

        if (!array_key_exists('SERVER_PROTOCOL', $serverParams)) {
            return $default;
        }

        return mb_substr($serverParams['SERVER_PROTOCOL'], 5);
    }

    /**
     * @param string $index
     *
     * @throws ApplicationException
     *
     * @return string
     */
    public static function getFolder(string $index): string
    {
        if (!array_key_exists($index, static::$app->folders)) {
            throw new ApplicationException('Invalid folder name');
        }

        return static::$app->folders[$index];
    }

    /**
     * @throws ApplicationException
     *
     * @return Application
     */
    public static function getInstance(): self
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        return static::$app;
    }

    /**
     * @throws ApplicationException
     *
     * @return Environment
     */
    public static function getConfig(): Environment
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        return static::$app->config;
    }

    /**
     * @param \Rancoud\Database\Database $database
     *
     * @throws ApplicationException
     */
    public static function setDatabase(\Rancoud\Database\Database $database): void
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        static::$app->database = $database;
    }

    /**
     * @throws ApplicationException
     *
     * @return null|\Rancoud\Database\Database
     */
    public static function getDatabase(): ?\Rancoud\Database\Database
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        return static::$app->database;
    }

    /**
     * @throws ApplicationException
     *
     * @return Router
     */
    public static function getRouter(): Router
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        return static::$app->router;
    }

    /**
     * @param $name
     *
     * @throws ApplicationException
     *
     * @return mixed
     */
    public static function getInBag($name)
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        return static::$app->bags[$name];
    }

    /**
     * @param $name
     *
     * @throws ApplicationException
     */
    public static function removeInBag($name)
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        unset(static::$app->bags[$name]);
    }

    /**
     * @param $name
     * @param $object
     *
     * @throws ApplicationException
     */
    public static function setInBag($name, $object)
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        static::$app->bags[$name] = $object;
    }

    /**
     * @throws Exception
     * @throws EnvironmentException
     */
    public function getDebugInfos(): array
    {
        $data = [];

        if ($this->isDebug === false) {
            return $data;
        }

        $data['memory'] = $this->getDebugMemory();
        $data['speed'] = $this->getDebugSpeed();
        $data['included_files'] = $this->getDebugIncludedFiles();
        $data['request'] = $this->getDebugRequest();
        $data['response'] = $this->getDebugResponse();
        $data['database'] = $this->getDebugDatabase();
        $data['session'] = $this->getDebugSession();

        return $data;
    }

    /**
     * @throws EnvironmentException
     *
     * @return ServerRequestInterface
     */
    protected function getDebugRequest(): ?ServerRequestInterface
    {
        if ($this->config->get('DEBUG_REQUEST') === true && $this->request !== null) {
            return $this->request;
        }

        return null;
    }

    /**
     * @throws EnvironmentException
     *
     * @return ResponseInterface
     */
    protected function getDebugResponse(): ?ResponseInterface
    {
        if ($this->config->get('DEBUG_RESPONSE') === true && $this->response !== null) {
            return $this->response;
        }

        return null;
    }

    /**
     * @throws EnvironmentException
     *
     * @return array|null
     */
    protected function getDebugDatabase(): ?array
    {
        if ($this->config->get('DEBUG_DATABASE') === true && $this->database !== null) {
            return $this->database->getSavedQueries();
        }

        return null;
    }

    /**
     * @throws EnvironmentException
     * @throws Exception
     *
     * @return array|null
     */
    protected function getDebugSession(): ?array
    {
        if ($this->config->get('DEBUG_SESSION') === true && isset($_SESSION)) {
            if (isset($_SESSION)) {
                return \Rancoud\Session\Session::getAll();
            }
        }

        return null;
    }

    /**
     * @throws EnvironmentException
     *
     * @return array|null
     */
    protected function getDebugMemory(): ?array
    {
        if ($this->config->get('DEBUG_MEMORY') === true) {
            $data = [];
            $memoryUsage = memory_get_usage();
            $memoryLimit = ini_get('memory_limit');
            $memoryPercentage = $this->getMemoryPercentage($memoryUsage, $memoryLimit);

            $data['usage'] = $memoryUsage;
            $data['limit'] = $memoryLimit;
            $data['percentage'] = $memoryPercentage;
            $data['summary'] = $this->convertMemoryUsageToHuman($memoryUsage) . ' / ';
            $data['summary'] .= $memoryLimit . ' = ' . $memoryPercentage . '%';

            return $data;
        }

        return null;
    }

    /**
     * @throws EnvironmentException
     *
     * @return float|null
     */
    protected function getDebugSpeed(): ?float
    {
        if ($this->config->get('DEBUG_SPEED') === true) {
            return round((microtime(true) - $this->appStart) * 1000000) / 1000000;
        }

        return null;
    }

    /**
     * @throws EnvironmentException
     *
     * @return array|null
     */
    protected function getDebugIncludedFiles(): ?array
    {
        if ($this->config->get('DEBUG_INCLUDED_FILES') === true) {
            return get_included_files();
        }

        return null;
    }

    /**
     * @param $size
     *
     * @return string
     */
    protected function convertMemoryUsageToHuman($size): string
    {
        $units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        $log = log($size, 1024);
        $unitIndex = (int) floor($log);
        $pow = pow(1024, $unitIndex);

        return round($size / $pow, 2) . $units[$unitIndex];
    }

    /**
     * @param $memoryUsage
     * @param $memoryLimit
     *
     * @return float
     */
    protected function getMemoryPercentage($memoryUsage, $memoryLimit): float
    {
        return round($memoryUsage * 100 / $this->convertMemoryLimitToBytes($memoryLimit), 2);
    }

    /**
     * @param $memoryLimit
     *
     * @return int
     */
    protected function convertMemoryLimitToBytes($memoryLimit)
    {
        $value = mb_substr($memoryLimit, 0, mb_strlen($memoryLimit) - 2);
        if (mb_substr($memoryLimit, -1) === 'K') {
            return $value * 1024;
        }

        if (mb_substr($memoryLimit, -1) === 'M') {
            return $value * 1024 * 1024;
        }

        if (mb_substr($memoryLimit, -1) === 'G') {
            return $value * 1024 * 1024 * 1024;
        }

        return $memoryLimit;
    }
}
