<?php

namespace Simply\Router;

use Simply\Router\Exception\MethodNotAllowedException;
use Simply\Router\Exception\RouteNotFoundException;

/**
 * Router.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Router
{
    private $provider;
    private $allowedMethods;

    public function __construct(RouteDefinitionProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param string $method
     * @param string $path
     * @return Route
     * @throws MethodNotAllowedException
     * @throws RouteNotFoundException
     */
    public function route(string $method, string $path): Route
    {
        if (!HttpMethod::isValidMethod($method)) {
            throw new \InvalidArgumentException("Invalid HTTP method '$method'");
        }

        $segments = array_values(array_filter(explode('/', $path), function (string $part): string {
            return \strlen($part) > 0;
        }));

        $this->allowedMethods = [];
        $routes = $this->matchRoutes($method, $segments);

        if (\count($routes) === 1) {
            return reset($routes);
        }

        if (\count($routes) > 1) {
            throw new \UnexpectedValueException("The given path '$path' matches more than one route");
        }

        if (\count($this->allowedMethods) > 0) {
            throw new MethodNotAllowedException(
                "The requested method '$method' is not within list of allowed methods",
                array_unique($this->allowedMethods)
            );
        }

        throw new RouteNotFoundException("The given path '$path' did not match any defined route");
    }

    private function matchRoutes(string $method, array $segments)
    {
        $matchedIds = $this->getMatchingIds($segments);
        return $this->getMatchingRoutes($matchedIds, $method, $segments);
    }

    private function getMatchingIds(array $segments): array
    {
        $count = \count($segments);
        $matched = [];

        $segmentCounts = $this->provider->getSegmentCounts();
        $matched[] = $segmentCounts[$count] ?? [];

        $segmentValues = $this->provider->getSegmentValues();

        for ($i = 0; $i < $count; $i++) {
            $matched[] = array_merge(
                $segmentValues[$i][$segments[$i]] ?? [],
                $segmentValues[$i]['#'] ?? []
            );
        }

        return $count > 0 ? array_intersect(... $matched) : $matched[0];
    }

    private function getMatchingRoutes(array $ids, string $method, array $segments): array
    {
        $routes = [];

        foreach ($ids as $id) {
            $definition = $this->provider->getRouteDefinition($id);

            if (!$definition->matchPatterns($segments, $values)) {
                continue;
            }

            if (!$definition->isMethodAllowed($method)) {
                array_push($this->allowedMethods, ... $definition->getMethods());
                continue;
            }

            $routes[] = new Route($definition, $method, $segments, $values);
        }

        return $routes;
    }

    public function getPath(string $name, array $parameters = []): string
    {
        $definition = $this->provider->getRouteDefinitionByName($name);
        return $definition->formatPath($parameters);
    }
}
