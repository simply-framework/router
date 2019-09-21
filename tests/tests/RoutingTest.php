<?php

namespace Simply\Router;

use PHPUnit\Framework\TestCase;
use Simply\Router\Collector\RouteCollector;
use Simply\Router\Compiler\DispatcherCompiler;

/**
 * RoutingTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RoutingTest extends TestCase
{
    /**
     * @dataProvider getSuccessfulRoutingTests
     */
    public function testSuccessfulRouting(array $routes, array $matches): void
    {
        $router = $this->buildDispatcher($routes);

        foreach ($matches as $arguments) {
            $this->assertRouteMatches($router, ... $arguments);
        }
    }

    public function getSuccessfulRoutingTests(): array
    {
        return [
            'Test routing with only single slash' => [
                [
                    ['GET', '/', 'A'],
                ],
                [
                    ['GET', '/', 'A', '/'],
                    ['GET', '', 'A', '/'],
                ],
            ],
            'Test routing with empty path' => [
                [
                    ['GET', '', 'A'],
                ],
                [
                    ['GET', '/', 'A', '/'],
                    ['GET', '', 'A', '/'],
                ],
            ],
            'Test routes with only single segment' => [
                [
                    ['GET', '/path', 'A'],
                    ['GET', '/route/', 'B'],
                ],
                [
                    ['GET', '/path/', 'A', '/path'],
                    ['GET', '/path', 'A', '/path'],
                    ['GET', 'path/', 'A', '/path'],
                    ['GET', '/route/', 'B', '/route/'],
                    ['GET', '/route', 'B', '/route/'],
                    ['GET', 'route/', 'B', '/route/'],
                ],
            ],
            'Test routes with multiple segments' => [
                [
                    ['GET', '/path/to/route/a/', 'A'],
                    ['GET', '/path/to/route/b', 'B'],
                ],
                [
                    ['GET', '/path/to/route/a/', 'A', '/path/to/route/a/'],
                    ['GET', '/path/to//route/a/', 'A', '/path/to/route/a/'],
                    ['GET', '/path/to/route/b/', 'B', '/path/to/route/b'],
                    ['GET', '//path//to//route//b//', 'B', '/path/to/route/b'],
                ],
            ],
            'Test redirection to GET on HEAD requests' => [
                [
                    ['GET', '/path/to/route/a', 'A'],
                    ['GET', '/path/to/route/b', 'B'],
                    ['HEAD', '/path/to/route/b', 'C'],
                ],
                [
                    ['GET', '/path/to/route/a', 'A', '/path/to/route/a'],
                    ['HEAD', '/path/to/route/a', 'A', '/path/to/route/a'],
                    ['GET', '/path/to/route/b', 'B', '/path/to/route/b'],
                    ['HEAD', '/path/to/route/b', 'C', '/path/to/route/b'],
                ],
            ],
            'Test placeholder segments in paths' => [
                [
                    ['GET', '/{param}/path/', 'A'],
                    ['GET', '/param/{path}/', 'B'],
                    ['GET', '/{param}/{path}/route/', 'C'],
                ],
                [
                    ['GET', '/foobar/path/', 'A', '/foobar/path/', ['param' => 'foobar']],
                    ['GET', '/param/foobar/', 'B', '/param/foobar/', ['path' => 'foobar']],
                    ['GET', '/param/path/route/', 'C', '/param/path/route/', ['param' => 'param', 'path' => 'path']],
                ]
            ],
            'Test pattern segments in routes' => [
                [
                    ['GET', '/path/{id:\d+}/', 'A'],
                    ['GET', '/path/{id:[a-f]+}/', 'B'],
                ],
                [
                    ['GET', '/path/123/', 'A', '/path/123/', ['id' => '123']],
                    ['GET', '/path/cafefeed/', 'B', '/path/cafefeed/', ['id' => 'cafefeed']],
                ]
            ],
            'Test multiple placeholders in single segment' => [
                [
                    ['GET', '/route/{paramA}.{paramB}/path/', 'A'],
                    ['GET', '/route/{paramA}-{paramB}/path/', 'B'],
                    ['GET', '/route/start_{paramA}_mid_{paramB}_end/path/', 'C'],
                ],
                [
                    ['GET', '/route/foo.bar/path/', 'A', '/route/foo.bar/path/', [
                        'paramA' => 'foo',
                        'paramB' => 'bar',
                    ]],
                    ['GET', '/route/foo-bar/path/', 'B', '/route/foo-bar/path/', [
                        'paramA' => 'foo',
                        'paramB' => 'bar',
                    ]],
                    ['GET', '/route/start_foo_mid_bar_end/path/', 'C', '/route/start_foo_mid_bar_end/path/', [
                        'paramA' => 'foo',
                        'paramB' => 'bar',
                    ]],
                ]
            ],
            'Test correct order of priority' => [
                [
                    ['GET', '/path/', 'A'],
                    ['GET', '/{param:[a-z]+}/', 'B'],
                    ['GET', '/{param}/', 'C'],
                ],
                [
                    ['GET', '/path/', 'A', '/path/'],
                    ['GET', '/foobar/', 'B', '/foobar/', ['param' => 'foobar']],
                    ['GET', '/123456/', 'C', '/123456/', ['param' => '123456']],
                ]
            ],
            'Test proper fall through for routes' => [
                [
                    ['GET', '/first/second/{id:\d+}/', 'A'],
                    ['GET', '/first/second/{id:[\da-f]+}/', 'B'],
                    ['GET', '/first/{param:\d+}/third/', 'C'],
                    ['GET', '/first/{param}/third/', 'D'],
                ],
                [
                    ['GET', '/first/second/123/', 'A', '/first/second/123/', ['id' => '123']],
                    ['GET', '/first/second/123abc/', 'B', '/first/second/123abc/', ['id' => '123abc']],
                    ['GET', '/first/123/third/', 'C', '/first/123/third/', ['param' => '123']],
                    ['GET', '/first/foobar/third/', 'D', '/first/foobar/third/', ['param' => 'foobar']],
                ]
            ],
        ];
    }

    private function assertRouteMatches(
        DispatcherInterface $router,
        string $method,
        string $path,
        string $expectedHandler,
        string $expectedPath,
        array $expectedParameters = []
    ): void {
        $result = $router->dispatch($method, $path);

        $this->assertCount(4, $result);
        $this->assertSame(DispatcherInterface::FOUND, $result[0]);
        $this->assertSame("handler.$expectedHandler", $result[1]);
        $this->assertSame($expectedParameters, $result[2]);
        $this->assertSame("name.$expectedHandler", $result[3]);
        $this->assertSame($expectedPath, $router->format($result[3], $result[2]));
    }

    private function buildDispatcher(array $routes): DispatcherInterface
    {
        $collector = new RouteCollector();

        foreach ($routes as [$method, $path, $handler]) {
            $collector->request($method, $path, "handler.$handler", "name.$handler");
        }

        $name = 'CompiledDispatcher' . bin2hex(random_bytes(16));

        $compiler = new DispatcherCompiler();
        $temp = tempnam(sys_get_temp_dir(), 'php');
        file_put_contents($temp, $compiler->compile($collector, $name));
        require $temp;
        unlink($temp);

        return new $name();
    }
}
