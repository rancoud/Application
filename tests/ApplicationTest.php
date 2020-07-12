<?php

declare(strict_types=1);

namespace Rancoud\Application\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Application\Application;
use Rancoud\Application\ApplicationException;
use Rancoud\Database\Configurator;
use Rancoud\Database\Database;
use Rancoud\Environment\Environment;
use Rancoud\Http\Message\Factory\Factory;
use Rancoud\Http\Message\Uri;
use Rancoud\Router\RouterException;
use Rancoud\Session\Session;

/**
 * Class ApplicationTest.
 */
class ApplicationTest extends TestCase
{
    protected string $folders = __DIR__ . DIRECTORY_SEPARATOR . 'folders';
    protected string $testsEnvFolder = __DIR__ . DIRECTORY_SEPARATOR . 'folders' . DIRECTORY_SEPARATOR . 'tests_env';

    protected function getFoldersWithAppEnv(): array
    {
        $ds = DIRECTORY_SEPARATOR;
        return [
            'ROOT' => $this->folders,
            'APP' => $this->folders . $ds . 'app',
            'WWW' => $this->folders . $ds . 'www',
            'ROUTES' => $this->folders . $ds . 'routes'
        ];
    }

    protected function getFoldersWithTestEnv(): array
    {
        $ds = DIRECTORY_SEPARATOR;
        return [
            'ROOT' => $this->folders,
            'APP' => $this->folders . $ds . 'app' . $ds,
            'WWW' => $this->folders . $ds . 'www' . $ds,
            'ROUTES' => $this->folders . $ds . 'tests_routes' . $ds
        ];
    }

    protected function getEnvironment(): Environment
    {
        $ds = DIRECTORY_SEPARATOR;
        return new Environment([__DIR__, $this->folders . $ds . 'tests_env' . $ds], 'test_valid_routes.env');
    }

    protected function getRequest(string $method, string $path): \Psr\Http\Message\ServerRequestInterface
    {
        return (new Factory())->createServerRequest($method, $path);
    }

    /**
     * @return Configurator
     * @throws \Rancoud\Database\DatabaseException
     */
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
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorCorrectMinimumFoldersArgument(): void
    {
        $folders = $this->getFoldersWithAppEnv();
        $app = new Application(['ROOT' => $folders['ROOT'], 'ROUTES' => $folders['ROUTES']]);

        static::assertSame(Application::class, get_class($app));
        static::assertNotSame($folders['ROOT'], Application::getFolder('ROOT'));
        static::assertNotSame($folders['ROUTES'], Application::getFolder('ROUTES'));
        static::assertSame($folders['ROOT'] . DIRECTORY_SEPARATOR, Application::getFolder('ROOT'));
        static::assertSame($folders['ROUTES'] . DIRECTORY_SEPARATOR, Application::getFolder('ROUTES'));
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorCorrectExtraFoldersArgument(): void
    {
        $folders = $this->getFoldersWithAppEnv();
        $app = new Application($folders);

        static::assertSame(Application::class, get_class($app));
        static::assertNotSame($folders['ROOT'], Application::getFolder('ROOT'));
        static::assertNotSame($folders['ROUTES'], Application::getFolder('ROUTES'));
        static::assertNotSame($folders['APP'], Application::getFolder('APP'));
        static::assertNotSame($folders['WWW'], Application::getFolder('WWW'));
        static::assertSame($folders['ROOT'] . DIRECTORY_SEPARATOR, Application::getFolder('ROOT'));
        static::assertSame($folders['ROUTES'] . DIRECTORY_SEPARATOR, Application::getFolder('ROUTES'));
        static::assertSame($folders['APP'] . DIRECTORY_SEPARATOR, Application::getFolder('APP'));
        static::assertSame($folders['WWW'] . DIRECTORY_SEPARATOR, Application::getFolder('WWW'));
    }


    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorEmptyFoldersArgument(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Missing folder name. Use ROOT, ROUTES');

        new Application([]);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorIncorrectFoldersValueArgumentNotString(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('"ROOT" is not a valid folder.');

        new Application(['ROOT' => true]);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorIncorrectFoldersValueArgumentNotFolder(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('"ROOT" is not a valid folder.');

        new Application(['ROOT' => '/invalid_folder/']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetInvalidFolder(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Invalid folder name');

        $folders = $this->getFoldersWithAppEnv();
        new Application($folders);
        Application::getFolder('NONE');
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorEnvironment(): void
    {
        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_empty.env'));

        static::assertSame(Application::class, get_class($app));
    }


    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorEnvironmentBadTimezone(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Invalid timezone: invalid. Check DateTimeZone::listIdentifiers()');

        new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_bad_timezone.env'));
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorEnvironmentGoodTimezone(): void
    {
        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_good_timezone.env'));

        static::assertSame(Application::class, get_class($app));
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorEnvironmentInvalidRoutes(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Invalid routes');

        new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_invalid_routes.env'));
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConstructorEnvironmentInvalidRoute(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Invalid route file');

        new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_invalid_route.env'));
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testRunFound(): void
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
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testRunFoundButNoHandle(): void
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/no_handle');
        $response = $app->run($request);

        static::assertNull($response);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testRunNotFound(): void
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        $request = $this->getRequest('GET', '/not_found');
        $response = $app->run($request);

        static::assertNull($response);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testRunRouterException404Invalid(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('The default404 is invalid');

        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_router_404.env'));
        $request = $this->getRequest('GET', '/no_handle');
        $app->run($request);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testRunRouterException(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('The default404 is invalid');

        $app = new Application($this->getFoldersWithTestEnv(), new Environment([$this->testsEnvFolder], 'test_router_404.env'));
        $request = $this->getRequest('GET', '/no_handle');
        $app->run($request);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testRunFoundChangeServerProtocol(): void
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
        $request = (new Factory())->createServerRequestFromGlobals();
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
     * @throws ApplicationException
     */
    public function testGetEmptyInstance(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::getInstance();
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     */
    public function testGetEmptyRouter(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::getRouter();
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     */
    public function testGetEmptyConfig(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::getConfig();
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     */
    public function testGetEmptyDatabase(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::getDatabase();
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Database\DatabaseException
     */
    public function testSetEmptyDatabase(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        $configurator = $this->createConfigurator();
        $database = new Database($configurator);
        Application::setDatabase($database);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     */
    public function testGetEmptyBag(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::getInBag('a');
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     */
    public function testSetEmptyBag(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::setInBag('a', 'b');
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     */
    public function testRemoveEmptyBag(): void
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Empty Instance');

        Application::removeInBag('a');
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetInstance(): void
    {
        $app = new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());
        static::assertSame($app, Application::getInstance());
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetConfig(): void
    {
        new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());

        $env = $this->getEnvironment();
        $env->load();

        static::assertEquals($env, Application::getConfig());
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetRouter(): void
    {
        new Application($this->getFoldersWithTestEnv(), $this->getEnvironment());

        $router = Application::getRouter();

        static::assertSame('/no_handle', $router->generateUrl('test_no_handle'));
        static::assertSame('/', $router->generateUrl('test_home'));
        static::assertCount(2, $router->getRoutes());
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetSetDatabase(): void
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
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetSetRemoveInBag(): void
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
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Rancoud\Environment\EnvironmentException
     * @throws \Exception
     */
    public function testGetDebug(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        static::assertSame('1', ini_get('display_errors'));

        $infos = $app->getDebugInfos();

        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);
        
        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);
        
        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);
        
        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);
        
        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);

        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
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
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNotNull($infos['database']);
        static::assertIsArray($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNotNull($infos['session']);
        static::assertIsArray($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Rancoud\Environment\EnvironmentException
     * @throws \Exception
     */
    public function testGetDebugExcludeDatabase(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_database.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        static::assertSame('0', ini_get('display_errors'));

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);

        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
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
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNotNull($infos['session']);
        static::assertIsArray($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetDebugExcludeIncludeFiles(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_include_files.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertNull($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetDebugExcludeMemory(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_memory.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertNull($infos['memory']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetDebugExcludeRequest(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_request.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);

        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetDebugExcludeResponse(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_response.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);


        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws RouterException
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Rancoud\Environment\EnvironmentException
     * @throws \Exception
     */
    public function testGetDebugExcludeSession(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_session.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);


        $request = $this->getRequest('GET', '/');
        $request = $request->withProtocolVersion('2');
        $app->run($request);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
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
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertIsArray($infos['run_elapsed_times']);
        static::assertNotEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNotNull($infos['request']);
        static::assertInstanceOf(\Rancoud\Http\Message\ServerRequest::class, $infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNotNull($infos['response']);
        static::assertInstanceOf(\Rancoud\Http\Message\Response::class, $infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNotNull($infos['database']);
        static::assertIsArray($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testGetDebugExcludeRunElapsedTimes(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_run_elapsed_times.env');
        $app = new Application($this->getFoldersWithTestEnv(), $env);

        $infos = $app->getDebugInfos();
        static::assertArrayHasKey('memory', $infos);
        static::assertArrayHasKey('usage', $infos['memory']);
        static::assertArrayHasKey('limit', $infos['memory']);
        static::assertArrayHasKey('percentage', $infos['memory']);
        static::assertArrayHasKey('summary', $infos['memory']);

        static::assertIsInt($infos['memory']['usage']);
        static::assertIsString($infos['memory']['limit']);
        static::assertIsFloat($infos['memory']['percentage']);
        static::assertIsString($infos['memory']['summary']);

        static::assertArrayHasKey('run_elapsed_times', $infos);
        static::assertEmpty($infos['run_elapsed_times']);

        static::assertArrayHasKey('included_files', $infos);
        static::assertIsArray($infos['included_files']);

        static::assertArrayHasKey('request', $infos);
        static::assertNull($infos['request']);

        static::assertArrayHasKey('response', $infos);
        static::assertNull($infos['response']);

        static::assertArrayHasKey('database', $infos);
        static::assertNull($infos['database']);

        static::assertArrayHasKey('session', $infos);
        static::assertNull($infos['session']);
    }

    /**
     * @runInSeparateProcess
     * @throws ApplicationException
     * @throws \Rancoud\Environment\EnvironmentException
     */
    public function testConvertMemoryLimitToBytes(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $env = new Environment([$this->folders . $ds . 'tests_env' . $ds], 'test_debug_exclude_run_elapsed_times.env');
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