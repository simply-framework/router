<?php

namespace Simply\Router\Collector;

/**
 * RouteCollector.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteCollector
{
    /** @var CollectedRoute[] */
    private $routes;

    /**
     * @return CollectedRoute[]
     */
    public function getCollectedRoutes(): array
    {
        return $this->routes;
    }

    public function request($method, string $path, $handler, string $name = null): self
    {
        $this->routes[] = new CollectedRoute(is_array($method) ? $method : [$method], $path, $handler, $name);

        return $this;
    }
}
