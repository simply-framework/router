<?php

namespace Simply\Router;

use Simply\Router\Exception\MethodNotAllowedException;
use Simply\Router\Exception\RouteNotFoundException;

/**
 * Class for routing requested methods and paths to specific routes.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018-2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Router
{
    /** @var RouteDefinitionProvider The route definition provider */
    private $provider;

    /** @var string[] List of methods that would be allowed for the routed path */
    private $allowedMethods;

    /**
     * Router constructor.
     * @param RouteDefinitionProvider $provider The route definition provider
     */
    public function __construct(RouteDefinitionProvider $provider)
    {
        $this->provider = $provider;
        $this->allowedMethods = [];
    }

    /**
     * Routes the given path with the given HTTP request method to a specific route.
     * @param string $method The HTTP request method
     * @param string $path The decoded request path
     * @return Route Matching route
     * @throws MethodNotAllowedException If the path matches at least one route, but the method is not allowed
     * @throws RouteNotFoundException If no route matches the given path
     */
    public function route(string $method, string $path): Route
    {
        $segments = preg_split('#/#', $path, -1, \PREG_SPLIT_NO_EMPTY);
        $static = $this->provider->getStaticRoute($method, implode('/', $segments));

        if ($static !== null) {
            return new Route($this->provider->getRouteDefinition($static), $method, $segments, []);
        }

        if (!HttpMethod::isValid($method)) {
            throw new \InvalidArgumentException("Invalid HTTP method '$method'");
        }

        $this->allowedMethods = [];
        $routes = $this->matchRoutes($method, $segments);

        if (\count($routes) === 1) {
            return $routes[0];
        }

        if (\count($routes) !== 0) {
            throw new \UnexpectedValueException("The given path '$path' matches more than one route");
        }

        if (\count($this->allowedMethods) > 0) {
            if (\in_array(HttpMethod::GET, $this->allowedMethods, true)) {
                $this->allowedMethods[] = HttpMethod::HEAD;
            }

            throw new MethodNotAllowedException(
                "The requested method '$method' is not within list of allowed methods",
                array_values(array_intersect(HttpMethod::getAll(), $this->allowedMethods))
            );
        }

        throw new RouteNotFoundException("The given path '$path' did not match any defined route");
    }

    /**
     * Returns routes that match the given HTTP request method and path segments.
     * @param string $method The HTTP request method
     * @param string[] $segments The requested path segments
     * @return Route[] List of matching routes
     */
    private function matchRoutes(string $method, array $segments): array
    {
        if ($segments === []) {
            return [];
        }

        $matchedIds = $this->getDynamicRouteIds($segments);
        return $this->getMatchingRoutes($matchedIds, $method, $segments);
    }

    /**
     * Returns a list of route ids for dynamic routes that have matching static path segments.
     * @param string[] $segments The requested path segments
     * @return int[] List of route ids for dynamic routes that have matching static path segments
     */
    private function getDynamicRouteIds(array $segments): array
    {
        $matched = [];
        $index = 0;

        do {
            $matched[] =
                $this->provider->getRoutesBySegmentValue($index, $segments[$index]) +
                $this->provider->getRoutesBySegmentValue($index, RouteDefinition::DYNAMIC_SEGMENT);
            $index++;
        } while (isset($segments[$index]));

        $matched[] = $this->provider->getRoutesBySegmentCount(\count($segments));

        return array_values(array_intersect_key(... $matched));
    }

    /**
     * Returns the routes for the given ids that match the requested method and segments.
     * @param int[] $ids List of route ids to match
     * @param string $method The HTTP request method
     * @param string[] $segments The requested path segments
     * @return Route[] List of matching routes
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

    /**
     * Returns the encoded URL for the route with the given name.
     * @param string $name Name of the route
     * @param string[] $parameters Values for the route parameters
     * @return string The encoded URL for the route
     */
    public function generateUrl(string $name, array $parameters = []): string
    {
        $definition = $this->provider->getRouteDefinitionByName($name);
        return $definition->formatUrl($parameters);
    }
}
