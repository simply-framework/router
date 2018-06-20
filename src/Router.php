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
    /** @var RouteDefinitionProvider The route definition provider */
    private $provider;

    /** @var string[] List of methods that would be allowed for the routed path */
    private $allowedMethods;

    public function __construct(RouteDefinitionProvider $provider)
    {
        $this->provider = $provider;
        $this->allowedMethods = [];
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

        $segments = array_values(array_filter(explode('/', $path), function (string $part): bool {
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
            if (\in_array(HttpMethod::GET, $this->allowedMethods, true)) {
                $this->allowedMethods[] = HttpMethod::HEAD;
            }

            throw new MethodNotAllowedException(
                "The requested method '$method' is not within list of allowed methods",
                array_unique($this->allowedMethods)
            );
        }

        throw new RouteNotFoundException("The given path '$path' did not match any defined route");
    }

    /**
     * Returns routes that match the given HTTP request method and path segments.
     * @param string $method The HTTP request method
     * @param string[] $segments The path segments
     * @return Route[] List of matching routes
     */
    private function matchRoutes(string $method, array $segments): array
    {
        $staticRoutes = $this->provider->getStaticRoutes();
        $path = implode('/', $segments);

        if (isset($staticRoutes[$path])) {
            $routes = $this->getMatchingRoutes($staticRoutes[$path], $method, $segments);

            if ($routes) {
                return $routes;
            }
        }

        $matchedIds = $this->getIntersectingIds($segments);
        return $this->getMatchingRoutes($matchedIds, $method, $segments);
    }

    /**
     * Returns a list of route ids for routes that have matching static path segments.
     * @param string[] $segments The routed path segments
     * @return int[] List of route ids for routes that have matching static path segments
     */
    private function getIntersectingIds(array $segments): array
    {
        $count = \count($segments);
        $matched = [];

        $segmentCounts = $this->provider->getSegmentCounts();
        $segmentValues = $this->provider->getSegmentValues();

        if (empty($segmentCounts[$count])) {
            return [];
        }

        for ($i = 0; $i < $count; $i++) {
            $matched[] = array_merge(
                $segmentValues[$i][$segments[$i]] ?? [],
                $segmentValues[$i]['#'] ?? []
            );
        }


        $matched[] = $segmentCounts[$count];

        return $count > 0 ? array_intersect(... $matched) : $matched[0];
    }

    /**
     * Returns the routes from given ids the match the requested method and segments.
     * @param int[] $ids The route ids to match
     * @param string $method The request HTTP method
     * @param string[] $segments The request path segments
     * @return Route[] The matched routes
     */
    private function getMatchingRoutes(array $ids, string $method, array $segments): array
    {
        $routes = [];

        foreach ($ids as $id) {
            $definition = $this->provider->getRouteDefinition($id);
            $values = [];

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
