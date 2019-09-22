<?php

use FastRoute\RouteCollector;
use Simply\Router\Compiler\DispatcherCompiler;
use Simply\Router\Route;
use Simply\Router\RouteDefinition;
use Simply\Router\RouteDefinitionProvider;
use Simply\Router\Router;

error_reporting(\E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require __DIR__ . '/../../vendor/autoload.php';

$runtime = 1.0;
$sets = [
    //*
    [
        'name' => 'Empty Route',
        'requests' => [
            ['GET', '/', 'handler'],
        ],
        'routes' => [
            ['GET', '/', 'handler'],
        ],
    ], //*/
    //*
    [
        'name' => 'Single Placeholder path',
        'requests' => [
            ['GET', '/articlename/', 'route-a'],
        ],
        'routes' => [
            ['GET', '/{name}/', 'route-a'],
        ],
    ], //*/
    //*
    [
        'name' => 'Placeholder in middle',
        'requests' => [
            ['GET', '/foo/123/bar', 'handler'],
        ],
        'routes' => [
            ['GET', '/foo/{id}/bar', 'handler'],
        ],
    ], //*/
    //*
    [
        'name' => 'Complex Scenario',
        'requests' => [
            ['GET', '/foo/gallery/3/add/', 'route-d'],
        ],
        'routes' => [
            ['GET', '/{name}/', 'route-a'],
            ['GET', '/{name}/images/', 'route-b'],
            ['GET', '/{name}/identity/', 'route-c'],
            ['GET', '/{name}/gallery/{id:\d+}/add/', 'route-d'],
            ['GET', '/{name}/gallery/{id:\d+}/', 'route-e'],
        ],
    ], //*/
    //*
    [
        'name' => 'Real Scenario',
        'requests' => require __DIR__ . '/requests.php',
        'routes' => require __DIR__ . '/routes.php',
    ], //*/
];

$getTimer = function (): float {
    $timer = hrtime();
    return $timer[0] + $timer[1] / 1e9;
};

$benchmark = function (Closure $callback, array $requests, float $runtime) use ($getTimer): int {
    reset($requests);
    $runtime *= 1e9;
    $timer = hrtime(true);
    $count = 0;

    while (hrtime(true) - $timer < $runtime) {
        [$method, $uri, $expectedHandler] = current($requests);
        $handler = $callback($method, $uri);

        if ($handler !== $expectedHandler) {
            throw new RuntimeException('Unexpected handler returned');
        }

        $count++;

        if (!next($requests)) {
            reset($requests);
        }
    }

    return $count;
};

$formatResult = function (string $name, int $count, float $runtime, float $percentage = null): string {
    $result = sprintf('  - %-12s %11s (%11s / s)', $name, number_format($count), number_format($count / $runtime));

    if ($percentage !== null) {
        $result .= sprintf(' %.2f%%', $percentage * 100);
    }

    return $result;
};

foreach ($sets as ['name' => $name, 'requests' => $requests, 'routes' => $routes]) {
    echo "$name:\n";

    $dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $collector) use ($routes) {
        foreach ($routes as [$method, $path, $handler]) {
            $collector->addRoute($method, $path, $handler);
        }
    });

    $fastCount = $benchmark(function (string $method, string $uri) use ($dispatcher): string {
        return $dispatcher->dispatch($method, $uri)[1];
    }, $requests, $runtime);

    echo $formatResult('FastRoute', $fastCount, $runtime) . "\n";

    $collector = new \Simply\Router\Collector\RouteCollector();

    foreach ($routes as [$method, $path, $handler]) {
        $collector->request($method, $path, $handler);
    }

    $className = 'CompiledDispatcher' . bin2hex(random_bytes(20));
    $compiler = new DispatcherCompiler();
    $filename = tempnam(sys_get_temp_dir(), 'php');
    file_put_contents($filename, $compiler->compile($collector, $className), 5);
    require $filename;

    $router = new $className();

    $count = $benchmark(function (string $method, string $uri) use ($router): string {
        return $router->dispatch($method, $uri)[1];
    }, $requests, $runtime);

    echo $formatResult('SimplyRouter', $count, $runtime, $count / $fastCount) . "\n";
}
