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
    /** @var array */
    protected array $folders = [];

    /** @var ?Application */
    protected static ?Application $app = null;

    /** @var ?Router */
    protected ?Router $router = null;

    /** @var Environment */
    protected $config = [];

    /** @var ?\Rancoud\Database\Database */
    protected ?\Rancoud\Database\Database $database = null;

    /** @var ServerRequestInterface|null */
    protected ?ServerRequestInterface $request = null;

    /** @var ResponseInterface|null */
    protected ?ResponseInterface $response = null;

    /** @var array */
    protected array $bags = [];

    /** @var array */
    protected array $runElapsedTimes = [];

    /** @var bool */
    protected bool $isDebug = false;

    /**
     * App constructor.
     *
     * @param array            $folders
     * @param Environment|null $env
     *
     * @throws ApplicationException
     * @throws EnvironmentException
     */
    public function __construct(array $folders, ?Environment $env = null)
    {
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
        $propsCount = \count($props);
        $validProps = \implode(', ', $props);
        foreach ($folders as $name => $folder) {
            if (!\is_string($folder) || !\file_exists($folder)) {
                throw new ApplicationException('"' . $name . '" is not a valid folder.');
            }

            if (\mb_substr($folder, -1) !== \DIRECTORY_SEPARATOR) {
                $folder .= \DIRECTORY_SEPARATOR;
            }

            $this->folders[$name] = $folder;
            if (\in_array($name, $props, true)) {
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
    protected function loadEnvironment(?Environment $env = null): void
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
        }

        if ($this->isDebug && $this->config->get('DEBUG_PHP') === true) {
            \error_reporting(-1);
            \ini_set('display_errors', '1');
        } else {
            \ini_set('display_errors', '0');
            \error_reporting(\E_ALL & ~\E_NOTICE & ~\E_DEPRECATED & ~\E_STRICT & ~\E_USER_NOTICE & ~\E_USER_DEPRECATED);
        }
    }

    /**
     * @throws EnvironmentException
     * @throws ApplicationException
     */
    protected function setupTimezone(): void
    {
        $timezone = $this->config->get('TIMEZONE', 'UTC');

        $allTimezones = DateTimeZone::listIdentifiers();
        if (\in_array($timezone, $allTimezones, true)) {
            \date_default_timezone_set($timezone);
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

        if (!\is_string($routes)) {
            throw new ApplicationException('Invalid routes');
        }
        $routes = \explode(',', $routes);

        foreach ($routes as $route) {
            if (!\file_exists($this->folders['ROUTES'] . $route . '.php')) {
                throw new ApplicationException('Invalid route file');
            }

            $this->loadRouteFile($route . '.php');
        }
    }

    protected function loadAllRoutesFilesInRoutesFolder(): void
    {
        $res = \opendir($this->folders['ROUTES']);
        while (true) {
            $routeFile = \readdir($res);
            if ($routeFile === false) {
                break;
            }

            if (\mb_strripos($routeFile, '.php') === \mb_strlen($routeFile) - 4) {
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
     * @return Response|null
     */
    public function run(ServerRequestInterface $request): ?Response
    {
        $runStart = \microtime(true);

        $this->router->findRouteRequest($request);

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

        $this->runElapsedTimes[] = \round((\microtime(true) - $runStart) * 1000000) / 1000000;

        return $response;
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

        if (!\array_key_exists('SERVER_PROTOCOL', $serverParams)) {
            return $default;
        }

        return \mb_substr($serverParams['SERVER_PROTOCOL'], 5);
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
        if (!\array_key_exists($index, static::$app->folders)) {
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
     * @throws EnvironmentException
     */
    public static function setDatabase(\Rancoud\Database\Database $database): void
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        if (static::$app->isDebug && static::$app->config->get('DEBUG_DATABASE') === true) {
            $database->enableSaveQueries();
        }

        static::$app->database = $database;
    }

    /**
     * @throws ApplicationException
     *
     * @return \Rancoud\Database\Database|null
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
     * @param string $name
     *
     * @throws ApplicationException
     *
     * @return mixed
     */
    public static function getFromBag(string $name)
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        return static::$app->bags[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @throws ApplicationException
     */
    public static function removeFromBag(string $name): void
    {
        if (static::$app === null) {
            throw new ApplicationException('Empty Instance');
        }

        unset(static::$app->bags[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $object
     *
     * @throws ApplicationException
     */
    public static function setInBag(string $name, $object): void
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
        $data['run_elapsed_times'] = $this->getDebugRunElapsedTimes();
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
        if ($this->request !== null && $this->config->get('DEBUG_REQUEST') === true) {
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
        if ($this->response !== null && $this->config->get('DEBUG_RESPONSE') === true) {
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
        if ($this->database !== null && $this->config->get('DEBUG_DATABASE') === true) {
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
        if (isset($_SESSION) && $this->config->get('DEBUG_SESSION') === true) {
            return \Rancoud\Session\Session::getAll();
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
            $memoryUsage = \memory_get_usage();
            $memoryLimit = \ini_get('memory_limit');
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
     * @return array
     */
    protected function getDebugRunElapsedTimes(): array
    {
        if ($this->config->get('DEBUG_RUN_ELAPSED_TIMES') === true) {
            return $this->runElapsedTimes;
        }

        return [];
    }

    /**
     * @throws EnvironmentException
     *
     * @return array|null
     */
    protected function getDebugIncludedFiles(): ?array
    {
        if ($this->config->get('DEBUG_INCLUDED_FILES') === true) {
            return \get_included_files();
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
        $log = \log($size, 1024);
        $unitIndex = (int) \floor($log);
        $pow = 1024 ** $unitIndex;

        return \round($size / $pow, 2) . $units[$unitIndex];
    }

    /**
     * @param $memoryUsage
     * @param $memoryLimit
     *
     * @return float
     */
    protected function getMemoryPercentage($memoryUsage, $memoryLimit): float
    {
        return \round($memoryUsage * 100 / $this->convertMemoryLimitToBytes($memoryLimit), 2);
    }

    /**
     * @param $memoryLimit
     *
     * @return int
     */
    protected function convertMemoryLimitToBytes($memoryLimit)
    {
        $value = (int) \mb_substr($memoryLimit, 0, -1);
        if (\mb_substr($memoryLimit, -1) === 'K') {
            return $value * 1024;
        }

        if (\mb_substr($memoryLimit, -1) === 'M') {
            return $value * 1024 * 1024;
        }

        if (\mb_substr($memoryLimit, -1) === 'G') {
            return $value * 1024 * 1024 * 1024;
        }

        return $memoryLimit;
    }
}
