<?php

namespace Simply\Router;

use PHPUnit\Framework\TestCase;
use Simply\Router\Exception\MethodNotAllowedException;
use Simply\Router\Exception\RouteNotFoundException;

/**
 * RouterTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouterTest extends TestCase
{
    public function testRoutingEmptyPath()
    {
        $router = $this->getRouter([
            ['test.route', 'GET', '/']
        ]);

        $this->assertRoute($router, 'GET', '/', 'test.route', '/');
        $this->assertRoute($router, 'GET', '', 'test.route', '/');
    }

    public function testSimpleRoute()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/path/to/route/a/'],
            ['test.b', 'GET', '/path/to/route/b'],
        ]);

        $this->assertRoute($router, 'GET', '/path/to/route/a/', 'test.a', '/path/to/route/a/');
        $this->assertRoute($router, 'GET', '/path/to/route/b/', 'test.b', '/path/to/route/b');
    }

    public function testRoutingInvalidHttpMethod()
    {
        $router = $this->getRouter([]);

        $this->expectException(\InvalidArgumentException::class);
        $router->route('NOT_A_HTTP_METHOD', '/');
    }

    public function testRouteNotFound()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/'],
            ['test.b', 'GET', '/path/to/route/b/'],
        ]);

        $this->expectException(RouteNotFoundException::class);
        $router->route('GET', '/path/to/route/');
    }

    public function testDifferentMethods()
    {
        $router = $this->getRouter([
            ['test.get', 'GET', '/route/path/'],
            ['test.post', 'POST', '/route/path/'],
        ]);

        $this->assertRoute($router, 'GET', '/route/path/', 'test.get', '/route/path/');
        $this->assertRoute($router, 'POST', '/route/path/', 'test.post', '/route/path/');
    }

    public function testMethodNotAllowed()
    {
        $router = $this->getRouter([
            ['test.get', 'GET', '/route/path/'],
            ['test.post', 'POST', '/route/path/'],
        ]);

        $exception = null;

        try {
            $router->route('PUT', '/route/path/');
        } catch (MethodNotAllowedException $caughtException) {
            $exception = $caughtException;
        }

        $this->assertInstanceOf(MethodNotAllowedException::class, $exception);
        $this->assertSame(['GET', 'POST'], $exception->getAllowedMethods());
    }

    private function assertRoute(
        Router $router,
        string $method,
        string $path,
        string $expectedHandler,
        string $expectedPath,
        array $expectedParameters = []
    ) {
        $route = $router->route($method, $path);

        $this->assertSame($method, $route->getMethod());
        $this->assertSame($expectedHandler, $route->getHandler());
        $this->assertSame($expectedPath, $route->getPath());

        foreach ($expectedParameters as $name => $value) {
            $this->assertSame($value, $route->getParameter($name));
        }
    }

    private function getRouter(array $routes): Router
    {
        $provider = new RouteDefinitionProvider();

        foreach ($routes as [$name, $methods, $path]) {
            if (!\is_array($methods)) {
                $methods = [$methods];
            }

            $provider->addRouteDefinition(new RouteDefinition($name, $methods, $path, $name));
        }

        return new Router($provider);
    }
}