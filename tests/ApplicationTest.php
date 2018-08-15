<?php

declare(strict_types=1);

namespace Rancoud\Application\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Application\Application;
use Rancoud\Application\ApplicationException;
use Rancoud\Database\Configurator;
use Rancoud\Database\Database;
use Rancoud\Environment\Environment;
use Rancoud\Http\Message\Factory\ServerRequestFactory;
use Rancoud\Http\Message\Uri;
use Rancoud\Router\RouterException;
use Rancoud\Session\Session;

/**
 * Class ApplicationTest.
 */
class ApplicationTest extends TestCase
{
    protected $folders =  __DIR__ . DIRECTORY_SEPARATOR . 'folders';
    protected $testsEnvFolder =  __DIR__ . DIRECTORY_SEPARATOR . 'folders' . DIRECTORY_SEPARATOR . 'tests_env';

    protected function getFoldersWithAppEnv()
    {
        $_ds = DIRECTORY_SEPARATOR;
        return [
            'ROOT' => $this->folders,
            'APP' => $this->folders . $_ds . 'app' . $_ds,
            'WWW' => $this->folders . $_ds . 'www' . $_ds,
            'ROUTES' => $this->folders . $_ds . 'routes' . $_ds
        ];
    }

    protected function getFoldersWithTestEnv()
    {
        $_ds = DIRECTORY_SEPARATOR;
        return [
            'ROOT' => $this->folders,
            'APP' => $this->folders . $_ds . 'app' . $_ds,
            'WWW' => $this->folders . $_ds . 'www' . $_ds,
            'ROUTES' => $this->folders . $_ds . 'tests_routes' . $_ds
        ];
    }

    protected function getEnvironment()
    {
        $_ds = DIRECTORY_SEPARATOR;
        return new Environment([dirname(__FILE__), $this->folders . $_ds . 'tests_env' . $_ds], 'test_valid_routes.env');
    }

    protected function getRequest(string $method, string $path)
    {
        return (new ServerRequestFactory())->createServerRequest($method, $path);
    }

    protected function createConfigurator(): Configurator
    {
        $params = [
            'engine'       => 'mysql',
            'host'         => 'localhost',
            'user'         => 'root',
            'password'     => '',
            'database'     => 'testpbbp'
        ];

        return new Configurator($params);
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorCorrectMinimumFoldersArgument()
    {
        $folders = $this->getFoldersWithAppEnv();
        $app = new Application(['ROOT' => $folders['ROOT'], 'ROUTES' => $folders['ROUTES']]);

        static::assertSame(Application::class, get_class($app));
        static::assertSame($folders['ROOT'], Application::getFolder('ROOT'));
        static::assertSame($folders['ROUTES'], Application::getFolder('ROUTES'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorCorrectExtraFoldersArgument()
    {
        $folders = $this->getFoldersWithAppEnv();
        $app = new Application($folders);

        static::assertSame(Application::class, get_class($app));
        static::assertSame($folders['ROOT'], Application::getFolder('ROOT'));
        static::assertSame($folders['ROUTES'], Application::getFolder('ROUTES'));
        static::assertSame($folders['APP'], Application::getFolder('APP'));
        static::assertSame($folders['WWW'], Application::getFolder('WWW'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorEmptyFoldersArgument()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Missing folder name. Use ROOT, ROUTES');

        new Application([]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorIncorrectFoldersValueArgumentNotString()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('"ROOT" is not a valid folder.');

        new Application(['ROOT' => true]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorIncorrectFoldersValueArgumentNotFolder()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('"ROOT" is not a valid folder.');

        new Application(['ROOT' => '/invalid_folder/']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetInvalidFolder()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Invalid folder name');

        $folders = $this->getFoldersWithAppEnv();
        new Application($folders);
        Application::getFolder('NONE');
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorEnvironment()
    {
        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_empty.env'));

        static::assertSame(Application::class, get_class($app));
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorEnvironmentBadTimezone()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Invalid timezone: invalid. Check DateTimeZone::listIdentifiers()');

        new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_bad_timezone.env'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorEnvironmentGoodTimezone()
    {
        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_good_timezone.env'));

        static::assertSame(Application::class, get_class($app));
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorEnvironmentInvalidRoutes()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Invalid routes');

        new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_invalid_routes.env'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructorEnvironmentInvalidRoute()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Invalid route file');

        new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_invalid_route.env'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunFound()
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/');
        $response = $app->run($request);

        static::assertNotNull($response);
        static::assertSame(200, $response->getStatusCode());
        static::assertEquals('home', $response->getBody());

        $infos = $app->getDebugInfos();
        static::assertSame([], $infos);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunFoundButNoHandle()
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/no_handle');
        $response = $app->run($request);

        static::assertNull($response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunNotFound()
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/not_found');
        $response = $app->run($request);

        static::assertNull($response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunRouterException404Invalid()
    {
        static::expectException(RouterException::class);
        static::expectExceptionMessage('The default404 is invalid');

        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_router_404.env'));
        $request = $this->getRequest('GET', '/no_handle');
        $app->run($request);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunRouterException()
    {
        static::expectException(RouterException::class);
        static::expectExceptionMessage('The default404 is invalid');

        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_router_404.env'));
        $request = $this->getRequest('GET', '/no_handle');
        $app->run($request);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunFoundChangeServerProtocol()
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('1.0');
        $response = $app->run($request);

        static::assertNotNull($response);
        static::assertSame(200, $response->getStatusCode());
        static::assertEquals('home', $response->getBody());
        static::assertSame('1.0', $response->getProtocolVersion());

        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $response = $app->run($request);

        static::assertNotNull($response);
        static::assertSame(200, $response->getStatusCode());
        static::assertEquals('home', $response->getBody());
        static::assertSame('2', $response->getProtocolVersion());

        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/0.9';
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = (new ServerRequestFactory())->createServerRequestFromGlobals();
        $request = $request->withMethod('GET');
        $request = $request->withUri((new Uri())->withPath('/'));
        $response = $app->run($request);

        static::assertNotNull($response);
        static::assertSame(200, $response->getStatusCode());
        static::assertEquals('home', $response->getBody());
        static::assertSame('0.9', $response->getProtocolVersion());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetEmptyInstance()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::getInstance();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetEmptyRouter()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::getRouter();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetEmptyConfig()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::getConfig();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetEmptyDatabase()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::getDatabase();
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetEmptyDatabase()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        $configurator = $this->createConfigurator();
        $database = new Database($configurator);
        Application::setDatabase($database);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetEmptyBag()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::getInBag('a');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetEmptyBag()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::setInBag('a', 'b');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveEmptyBag()
    {
        static::expectException(ApplicationException::class);
        static::expectExceptionMessage('Empty Instance');

        Application::removeInBag('a');
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testGetInstance()
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        static::assertSame($app, Application::getInstance());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetConfig()
    {
        new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());

        $env = $this->getEnvironment();
        $env->load();

        static::assertEquals($env, Application::getConfig());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetRouter()
    {
        new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());

        $router = Application::getRouter();

        static::assertSame('/no_handle', $router->generateUrl('test_no_handle'));
        static::assertSame('/', $router->generateUrl('test_home'));
        static::assertSame(2, count($router->getRoutes()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetSetDatabase()
    {
        new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());

        $db = Application::getDatabase();
        static::assertNull($db);

        $configurator = $this->createConfigurator();
        $database = new Database($configurator);
        Application::setDatabase($database);

        $db = Application::getDatabase();
        static::assertNotNull($db);
        static::assertSame($database, $db);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetSetRemoveInBag()
    {
        new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());

        $bag = Application::getInBag('a');
        static::assertNull($bag);

        Application::setInBag('b','c');

        $bag = Application::getInBag('b');
        static::assertSame('c', $bag);

        Application::removeInBag('b');

        $bag = Application::getInBag('b');
        static::assertNull($bag);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebug()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();

        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));
        
        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);
        
        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);
        
        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);
        
        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);

        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);

        $config = new Configurator([
            'engine'       => 'mysql',
            'host'         => '127.0.0.1',
            'user'         => 'root',
            'password'     => '',
            'database'     => 'test_database',
            'save_queries' => true]);
        $database = new Database($config);
        
        Application::setDatabase($database);
        Session::start();

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNotNull($infos['database']);
        static::assertTrue(is_array($infos['database']));

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNotNull($infos['session']);
        static::assertTrue(is_array($infos['session']));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeDatabase()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_database.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);

        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);

        $config = new Configurator([
            'engine'       => 'mysql',
            'host'         => '127.0.0.1',
            'user'         => 'root',
            'password'     => '',
            'database'     => 'test_database',
            'save_queries' => true]);
        $database = new Database($config);

        Application::setDatabase($database);
        Session::start();

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNotNull($infos['session']);
        static::assertTrue(is_array($infos['session']));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeIncludeFiles()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_include_files.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertNull($infos['included_files']);

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeMemory()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_memory.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertNull($infos['memory']);

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeRequest()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_request.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);

        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeResponse()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_response.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);


        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeSession()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_session.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);


        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);

        $config = new Configurator([
            'engine'       => 'mysql',
            'host'         => '127.0.0.1',
            'user'         => 'root',
            'password'     => '',
            'database'     => 'test_database',
            'save_queries' => true]);
        $database = new Database($config);

        Application::setDatabase($database);
        Session::start();

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertTrue(is_float($infos['speed']));

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNotNull($infos['request']);
        static::assertSame('Rancoud\Http\Message\ServerRequest', get_class($infos['request']));

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNotNull($infos['response']);
        static::assertSame('Rancoud\Http\Message\Response', get_class($infos['response']));

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNotNull($infos['database']);
        static::assertTrue(is_array($infos['database']));

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDebugExcludeSpeed()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_speed.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertTrue(array_key_exists('memory', $infos));
        static::assertTrue(array_key_exists('usage', $infos['memory']));
        static::assertTrue(array_key_exists('limit', $infos['memory']));
        static::assertTrue(array_key_exists('percentage', $infos['memory']));
        static::assertTrue(array_key_exists('summary', $infos['memory']));

        static::assertTrue(is_int($infos['memory']['usage']));
        static::assertTrue(is_string($infos['memory']['limit']));
        static::assertTrue(is_float($infos['memory']['percentage']));
        static::assertTrue(is_string($infos['memory']['summary']));

        static::assertTrue(array_key_exists('speed', $infos));
        static::assertNull($infos['speed']);

        static::assertTrue(array_key_exists('included_files', $infos));
        static::assertTrue(is_array($infos['included_files']));

        static::assertTrue(array_key_exists('request', $infos));
        static::assertNull($infos['request']);

        static::assertTrue(array_key_exists('response', $infos));
        static::assertNull($infos['response']);

        static::assertTrue(array_key_exists('database', $infos));
        static::assertNull($infos['database']);

        static::assertTrue(array_key_exists('session', $infos));
        static::assertNull($infos['session']);
    }

    /** @runInSeparateProcess */
    public function testConvertMemoryLimitToBytes()
    {
        $_ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $_ds . 'tests_env' . $_ds], 'test_debug_exclude_speed.env');
        $app = new ImplementApplication($this->getFoldersWithTestEnv(), $env);

        $value = $app->convertMemoryLimitToBytes('64M');
        static::assertSame(67108864, $value);

        $value = $app->convertMemoryLimitToBytes('256M');
        static::assertSame(268435456, $value);

        $value = $app->convertMemoryLimitToBytes('512M');
        static::assertSame(536870912, $value);

        $value = $app->convertMemoryLimitToBytes('64K');
        static::assertSame(65536, $value);

        $value = $app->convertMemoryLimitToBytes('1G');
        static::assertSame(1073741824, $value);

        $value = $app->convertMemoryLimitToBytes('-1');
        static::assertSame('-1', $value);
    }
}

class ImplementApplication extends Application
{
    public function convertMemoryLimitToBytes($memoryLimit)
    {
        return parent::convertMemoryLimitToBytes($memoryLimit);
    }

}