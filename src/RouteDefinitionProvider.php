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

    /** @var int[][] List of routes per number of segments */
    protected $segmentCounts = [];

    /** @var int[][][] List of routes by each segment */
    protected $segmentValues = [];

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
        $segments = $definition->getSegments();

        $this->routeDefinitions[$routeId] = $definition->getDefinitionCache();
        $this->routesByName[$name] = $routeId;

        if ($definition->isStatic()) {
            $path = implode('/', $segments);

            foreach ($definition->getMethods() as $method) {
                if (isset($this->staticRoutes[$method][$path])) {
                    throw new \InvalidArgumentException("A static route '$method $path' already exists");
                }

                $this->staticRoutes[$method][$path] = $routeId;
            }

            return;
        }

        foreach ($segments as $i => $segment) {
            $this->segmentValues[$i][$segment][$routeId] = $routeId;

            if (!isset($this->segmentCounts[$i])) {
                $this->segmentCounts[$i] = [];
            }
        }

        $this->segmentCounts[\count($segments)][$routeId] = $routeId;
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
        $counts = $encoder($this->segmentCounts);
        $values = $encoder($this->segmentValues);
        $definitions = $encoder($this->routeDefinitions);
        $names = $encoder($this->routesByName);

        return <<<TEMPLATE
<?php return new class extends \Simply\Router\RouteDefinitionProvider {
    protected \$staticRoutes = $statics;
    protected \$segmentCounts = $counts;
    protected \$segmentValues = $values;
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
     * Returns route ids with specific segment count.
     * @param int $count The number of segments in the path
     * @return int[] List of route ids with specific segment count
     */
    public function getRoutesBySegmentCount(int $count): array
    {
        return $this->segmentCounts[$count] ?? [];
    }

    /**
     * Returns route ids with specific value for specific segment.
     * @param int $segment The number of the segment
     * @param string $value The value for the segment or '/' dynamic segments
     * @return int[] List of route ids the match the given criteria
     */
    public function getRoutesBySegmentValue(int $segment, string $value): array
    {
        return $this->segmentValues[$segment][$value] ?? [];
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
