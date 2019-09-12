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

    /**
     * Router constructor.
     * @param RouteDefinitionProvider $provider The route definition provider
     */
    public function __construct(RouteDefinitionProvider $provider)
    {
        $this->provider = $provider;
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

        $routes = $this->matchRoutes($method, $segments);

        if (\count($routes) === 1) {
            return $routes[0];
        }

        if (\count($routes) > 1) {
            throw new \UnexpectedValueException("The given path '$path' matches more than one route");
        }

        if ($method === 'HEAD') {
            return $this->route('GET', $path)->withRequestMethod('HEAD');
        }

        if (!HttpMethod::isValid($method)) {
            throw new \InvalidArgumentException("Invalid HTTP method '$method'");
        }

        $allowedMethods = $this->detectAllowedMethods($segments);

        if (\count($allowedMethods) > 0) {
            throw new MethodNotAllowedException(
                "The requested method '$method' is not within list of allowed methods",
                $allowedMethods
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

        $matchedIds = $this->provider->getDynamicRoutes($method, $segments);
        $routes = [];

        foreach ($matchedIds as $id) {
            $definition = $this->provider->getRouteDefinition($id);
            $values = [];

            if (!$definition->matchPatterns($segments, $values)) {
                continue;
            }

            $routes[] = new Route($definition, $method, $segments, $values);
        }

        return $routes;
    }

    /**
     * Return list of all request methods that would be allowed for the given segments.
     * @param string[] $segments Segments of the requested path
     * @return string[] List of allowed request methods for the given path segments
     */
    private function detectAllowedMethods(array $segments): array
    {
        $methods = [];

        foreach ($this->provider->getMatchingStaticRoutes(implode('/', $segments)) as $routeId) {
            array_push($methods, ... $this->provider->getRouteDefinition($routeId)->getMethods());
        }

        foreach ($this->provider->getMatchingDynamicRoutes($segments) as $routeId) {
            $definition = $this->provider->getRouteDefinition($routeId);

            if ($definition->matchPatterns($segments)) {
                array_push($methods, ... $definition->getMethods());
            }
        }

        if (in_array('GET', $methods, true)) {
            $methods[] = 'HEAD';
        }

        return array_values(array_intersect(HttpMethod::getAll(), $methods));
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
