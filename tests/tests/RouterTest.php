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
            ['test.route', 'GET', '/'],
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

    public function testHeadFallback()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/path/to/route/'],
        ]);

        $route = $router->route('HEAD', '/path/to/route/');
        $this->assertSame('test.a', $route->getHandler());
        $this->assertSame('HEAD', $route->getMethod());
    }

    public function testArrayHandler()
    {
        $router = $this->getRouter([
            ['test', 'GET', '/test/path/', ['test', 'handler']],
        ]);

        $route = $router->route('GET', '/test/path/');
        $this->assertSame(['test', 'handler'], $route->getHandler());
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

    public function testRouteNotFoundOnEmptyRoutes()
    {
        $router = $this->getRouter([]);

        $this->expectException(RouteNotFoundException::class);
        $router->route('GET', '/path/to/route/that/does/not/exist/');
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
        $this->assertSame(['GET', 'POST', 'HEAD'], $exception->getAllowedMethods());
    }

    public function testPatternRouting()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/{param}/path/'],
            ['test.b', 'GET', '/param/{path}/'],
        ]);

        $this->assertRoute($router, 'GET', '/foobar/path/', 'test.a', '/foobar/path/', ['param' => 'foobar']);
        $this->assertRoute($router, 'GET', '/param/foobar/', 'test.b', '/param/foobar/', ['path' => 'foobar']);
    }

    public function testParameterCase()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/route/{Param}/path/'],
        ]);

        $route = $router->route('GET', '/route/foobar/path');

        $this->assertSame('foobar', $route->getParameter('Param'));

        $this->expectException(\InvalidArgumentException::class);
        $route->getParameter('param');
    }

    public function testMultipleMatches()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/route/{param}/path/'],
            ['test.b', 'GET', '/route/param/{path}/'],
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $router->route('GET', '/route/param/path/');
    }

    public function testMultipleParameterSegment()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/route/{paramA}-{paramB}/path/'],
            ['test.b', 'GET', '/route/start_{paramA}_mid_{paramB}_end/'],
        ]);

        $this->assertRoute($router, 'GET', '/route/foo-bar/path/', 'test.a', '/route/foo-bar/path/', [
            'paramA' => 'foo',
            'paramB' => 'bar',
        ]);

        $this->assertRoute($router, 'GET', '/route/start_foo_mid_bar_end/', 'test.b', '/route/start_foo_mid_bar_end/', [
            'paramA' => 'foo',
            'paramB' => 'bar',
        ]);
    }

    public function testDefiningSpecificPatterns()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/route/{param:[a-z]+}/path/'],
            ['test.b', 'GET', '/route/{param:\d+}/path/'],
        ]);

        $this->assertRoute($router, 'GET', '/route/abc/path/', 'test.a', '/route/abc/path/', ['param' => 'abc']);
        $this->assertRoute($router, 'GET', '/route/123/path/', 'test.b', '/route/123/path/', ['param' => '123']);
    }

    public function testIncompleteRouteMatch()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/route/{param:\d+}/path/'],
        ]);

        $this->expectException(RouteNotFoundException::class);
        $router->route('GET', '/route/123a/path/');
    }

    public function testCountInPattern()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/route/{param:\d{5}}/path/'],
        ]);

        $this->assertRoute($router, 'GET', '/route/12345/path/', 'test.a', '/route/12345/path/', ['param' => '12345']);
    }

    public function testMixedStaticDynamicRoute()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/path/to/route/'],
            ['test.b', 'POST', '/path/{param}/route/'],
        ]);

        $this->assertRoute($router, 'GET', '/path/to/route/', 'test.a', '/path/to/route/');
        $this->assertRoute($router, 'POST', '/path/to/route/', 'test.b', '/path/to/route/', ['param' => 'to']);
    }

    public function testRoutingWithEncodedCharacter()
    {
        $router = $this->getRouter([
            ['test.a', 'GET', '/path/encoded path/'],
        ]);

        $this->assertRoute($router, 'GET', '/path/encoded path/', 'test.a', '/path/encoded path/');
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

        $encodedPath = implode('/', array_map(function (string $value): string {
            return rawurlencode($value);
        }, explode('/', $expectedPath)));

        $this->assertSame($encodedPath, $route->getUrl());
        $this->assertSame($encodedPath, $router->generateUrl($expectedHandler, $expectedParameters));
    }

    private function getRouter(array $routes): Router
    {
        $provider = new RouteDefinitionProvider();

        foreach ($routes as $i => [$name, $methods, $path]) {
            if (!\is_array($methods)) {
                $methods = [$methods];
            }

            $handler = $routes[$i][3] ?? $name;

            $provider->addRouteDefinition(new RouteDefinition($name, $methods, $path, $handler));
        }

        $code = $provider->getCacheFile();
        $cachedProvider = eval(substr($code, 6));

        $this->assertSame($code, $cachedProvider->getCacheFile());

        return new Router($cachedProvider);
    }
}
