<?php

use FastRoute\RouteCollector;
use Simply\Router\RouteDefinition;
use Simply\Router\RouteDefinitionProvider;
use Simply\Router\Router;

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require __DIR__ . '/../vendor/autoload.php';

$runtime = 2.0;
$sets = [
    [
        'name' => 'Single Route',
        'requests' => [
            ['GET', '/', 'route-a'],
        ],
        'routes' => [
            ['GET', '/', 'route-a'],
        ],
    ],
    [
        'name' => 'Plain dynamic route',
        'requests' => [
            ['GET', '/articlename/', 'route-a'],
        ],
        'routes' => [
            ['GET', '/{name}/', 'route-a'],
        ]
    ]
];

$benchmark = function (Closure $callback, array $requests, float $runtime): int {
    reset($requests);
    $timer = microtime(true);
    $count = 0;

    while (microtime(true) - $timer < $runtime) {
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

$formatResult = function (string $name, int $count, float $runtime): string {
    return sprintf('  - %-12s %11s (%11s / s)', $name, number_format($count), number_format($count / $runtime));
};

foreach ($sets as ['name' => $name, 'requests' => $requests, 'routes' => $routes]) {
    echo "$name:\n";

    $dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $collector) use ($routes) {
        foreach ($routes as [$method, $path, $handler]) {
            $collector->addRoute($method, $path, $handler);
        }
    });

    $count = $benchmark(function (string $method, string $uri) use ($dispatcher): string {
        return $dispatcher->dispatch($method, $uri)[1];
    }, $requests, $runtime);

    echo $formatResult('FastRoute', $count, $runtime) . "\n";

    $provider = new RouteDefinitionProvider();

    foreach ($routes as [$method, $path, $handler]) {
        $provider->addRouteDefinition(new RouteDefinition($handler, [$method], $path, $handler));
    }

    $router = new Router($provider);

    $count = $benchmark(function (string $method, string $uri) use ($router): string {
        return $router->route($method, $uri)->getHandler();
    }, $requests, $runtime);

    echo $formatResult('SimplyRouter', $count, $runtime) . "\n";
}
