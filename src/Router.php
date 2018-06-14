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

    public function route(string $method, string $path)
    {
        if (!\in_array($method, HttpMethod::getHttpMethods(), true)) {
            throw new \InvalidArgumentException("Invalid HTTP method '$method'");
        }

        $segments = array_filter(explode('/', $path), function (string $part): string {
            return \strlen($part) > 0;
        });

        $this->allowedMethods = [];
        $routes = $this->matchRoutes($method, $segments);

        if (\count($routes) === 1) {
            return reset($routes);
        }

        if (\count($routes) > 1) {
            throw new \UnexpectedValueException("The given path matches more than one route");
        }

        if (\count($this->allowedMethods) > 0) {
            throw new MethodNotAllowedException($this->allowedMethods);
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

        $segmentCounts = $this->provider->getSegmentCounts();
        $matched = $segmentCounts[$count] ?? [];

        $segmentValues = $this->provider->getSegmentValues();

        for ($i = 0; $i < $count; $i++) {
            $matched[] = array_merge(
                $segmentValues[$i][$segments[$i]] ?? [],
                $segmentValues[$i]['#'] ?? []
            );
        }

        return array_intersect(... $matched);
    }

    private function getMatchingRoutes(array $ids, string $method, array $segments): array
    {
        foreach ($ids as $id) {
            $definition = $this->provider->getRouteDefinition($id);

            if (!$definition->matchPatterns($segments, $values)) {
                continue;
            }

            if (!$definition->isMethodAllowed($method)) {
                $this->allowedMethods[] = $definition->getAllowedMethods();
                continue;
            }

            $routes[] = new Route($definition, $method, $segments);
        }
    }
}
