<?php

namespace Simply\Router;

/**
 * Provides route definitions and matching arrays for the router.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018-2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteDefinitionProvider
{
    /** @var int[][] List of static paths to route */
    protected $staticRoutes = [];

    /** @var array Tree of arrays for matching dynamic routes */
    protected $dynamicRoutes = [];

    /** @var array[] Cache of all route definitions */
    protected $routeDefinitions = [];

    /** @var int[] List of routes by their name */
    protected $routesByName = [];

    /**
     * Adds a new route definition.
     * @param RouteDefinition $definition A new route definition to add
     */
    public function addRouteDefinition(RouteDefinition $definition): void
    {
        $name = $definition->getName();

        if (isset($this->routesByName[$name])) {
            throw new \InvalidArgumentException("Route with name '$name' already exists");
        }

        $routeId = \count($this->routeDefinitions);

        $this->routeDefinitions[$routeId] = $definition->getDefinitionCache();
        $this->routesByName[$name] = $routeId;

        if ($definition->isStatic()) {
            $this->addStaticRoute($routeId, $definition);
            return;
        }

        $this->addDynamicRoute($routeId, $definition);
    }

    /**
     * Adds matching rules for a static route.
     * @param int $routeId Id of the route
     * @param RouteDefinition $definition The definitions for the route
     */
    private function addStaticRoute(int $routeId, RouteDefinition $definition): void
    {
        $path = implode('/', $definition->getSegments());

        foreach ($definition->getMethods() as $method) {
            if (isset($this->staticRoutes[$method][$path])) {
                throw new \InvalidArgumentException("A static route '$method $path' already exists");
            }

            $this->staticRoutes[$method][$path] = $routeId;
        }
    }

    /**
     * Adds matching rules for a dynamic route.
     * @param int $routeId Id of the route
     * @param RouteDefinition $definition The definitions for the route
     */
    private function addDynamicRoute(int $routeId, RouteDefinition $definition): void
    {
        foreach ($definition->getMethods() as $method) {
            $tree = & $this->dynamicRoutes;

            foreach (array_merge([$method], $definition->getSegments()) as $segment) {
                if (!isset($tree[$segment])) {
                    $tree[$segment] = [];
                    ksort($tree);
                }

                $tree = & $tree[$segment];
            }

            $tree[''][$routeId] = true;
        }
    }

    /**
     * Returns PHP code for cached RouteDefinitionProvider that can be stored in file and included.
     * @param callable $encoder Encoding callback for values or null for default
     * @return string PHP code for cached RouteDefinitionProvider
     */
    public function getCacheFile(callable $encoder = null): string
    {
        if (\is_null($encoder)) {
            $encoder = function ($value): string {
                return var_export($value, true);
            };
        }

        $statics = $encoder($this->staticRoutes);
        $dynamics = $encoder($this->dynamicRoutes);
        $definitions = $encoder($this->routeDefinitions);
        $names = $encoder($this->routesByName);

        return <<<TEMPLATE
<?php return new class extends \Simply\Router\RouteDefinitionProvider {
    protected \$staticRoutes = $statics;
    protected \$dynamicRoutes = $dynamics;
    protected \$routeDefinitions = $definitions;
    protected \$routesByName = $names;
};
TEMPLATE;
    }

    /**
     * Returns route id for the given static route or null if it doesn't exist.
     * @param string $method The http method for the static route
     * @param string $path The static route path to search
     * @return int|null Route id for the static route or null if it doesn't exist
     */
    public function getStaticRoute(string $method, string $path): ?int
    {
        return $this->staticRoutes[$method][$path] ?? null;
    }

    /**
     * Return list of route ids that match for the given path regardless of request method.
     * @param string $path The static route path to search
     * @return int[] List of route ids that match the given static path
     */
    public function getMatchingStaticRoutes(string $path): array
    {
        $routes = [];

        foreach ($this->staticRoutes as $method => $_) {
            $routeId = $this->getStaticRoute($method, $path);

            if ($routeId !== null) {
                $routes[$routeId] = true;
            }
        }

        return array_keys($routes);
    }

    /**
     * Returns list of route ids that match the given request method and given segments.
     * @param string $method The http method for the dynamic route
     * @param string[] $segments Segments to search
     * @return int[] List of route ids that match the request method and segments
     */
    public function getDynamicRoutes(string $method, array $segments): array
    {
        $nextForks = [$this->dynamicRoutes[$method] ?? []];

        foreach ($segments as $segment) {
            $forks = $nextForks;
            $nextForks = [];

            foreach ($forks as $fork) {
                if (isset($fork[$segment])) {
                    $nextForks[] = $fork[$segment];
                }
                if (isset($fork[RouteDefinition::DYNAMIC_SEGMENT])) {
                    $nextForks[] = $fork[RouteDefinition::DYNAMIC_SEGMENT];
                }
            }

            if (empty($nextForks)) {
                return [];
            }
        }

        $routes = [];

        foreach ($nextForks as $fork) {
            if (isset($fork[''])) {
                $routes += $fork[''];
            }
        }

        return array_keys($routes);
    }

    /**
     * Returns all route ids for dynamic routes that match the given segments regardless of request method.
     * @param string[] $segments Path segments to match against
     * @return int[] List of dynamic route ids that match the given segments
     */
    public function getMatchingDynamicRoutes(array $segments): array
    {
        $routes = [];

        foreach ($this->dynamicRoutes as $method => $_) {
            $routes += array_flip($this->getDynamicRoutes($method, $segments));
        }

        return array_keys($routes);
    }

    /**
     * Returns a route definition by a specific id.
     * @param int $routeId Id of the route definition
     * @return RouteDefinition The route definition for the specific id
     */
    public function getRouteDefinition(int $routeId): RouteDefinition
    {
        if (!isset($this->routeDefinitions[$routeId])) {
            throw new \InvalidArgumentException("Invalid route id '$routeId'");
        }

        return RouteDefinition::createFromCache($this->routeDefinitions[$routeId]);
    }

    /**
     * Returns a route definition by the name of the route.
     * @param string $name The name of the route
     * @return RouteDefinition The route definition with the given name
     */
    public function getRouteDefinitionByName(string $name): RouteDefinition
    {
        if (!isset($this->routesByName[$name])) {
            throw new \InvalidArgumentException("Invalid route name '$name'");
        }

        return $this->getRouteDefinition($this->routesByName[$name]);
    }
}
