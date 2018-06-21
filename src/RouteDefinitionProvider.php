<?php

namespace Simply\Router;

/**
 * Provides route definitions and matching arrays for the router.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteDefinitionProvider
{
    /** @var array<string,int[]> List of static paths to route */
    protected $staticRoutes = [];

    /** @var array<int,int[]> List of routes per number of segments  */
    protected $segmentCounts = [];

    /** @var array<int,array<string,array<int,true>>> List of routes by each segment */
    protected $segmentValues = [];

    /** @var array[] Cache of all route definitions */
    protected $routeDefinitions = [];

    /** @var array<string,int> List of routes by their name */
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

        $id = \count($this->routeDefinitions);
        $segments = $definition->getSegments();

        $this->routeDefinitions[$id] = $definition->getDefinitionCache();
        $this->routesByName[$name] = $id;

        if ($definition->isStatic()) {
            $this->staticRoutes[implode('/', $segments)][] = $id;
        } else {
            foreach (array_values($segments) as $i => $segment) {
                $this->segmentValues[$i][$segment][$id] = true;
            }

            $this->segmentCounts += array_fill(0, \count($segments) + 1, []);
            $this->segmentCounts[\count($segments)][$id] = true;
        }
    }

    /**
     * Returns PHP code for cached RouteDefinitionProvider that can be stored in file and included.
     * @return string PHP code for cached RouteDefinitionProvider
     */
    public function getCacheFile(): string
    {
        $template = <<<'TEMPLATE'
<?php return new class extends \Simply\Router\RouteDefinitionProvider {
    protected $staticRoutes = ['STATICS'];
    protected $segmentCounts = ['COUNTS'];
    protected $segmentValues = ['VALUES'];
    protected $routeDefinitions = ['DEFINITIONS'];
    protected $routesByName = ['NAMES'];
};
TEMPLATE;

        return strtr($template, [
            "['STATICS']" => var_export($this->staticRoutes, true),
            "['COUNTS']" => var_export($this->segmentCounts, true),
            "['VALUES']" => var_export($this->segmentValues, true),
            "['DEFINITIONS']" => var_export($this->routeDefinitions, true),
            "['NAMES']" => var_export($this->routesByName, true),
        ]);
    }

    /**
     * Returns list of routes per static path.
     * @return array<string,int[]> List of routes per static path
     */
    public function getStaticRoutes(): array
    {
        return $this->staticRoutes;
    }

    /**
     * Returns list of routes per number of segments.
     * @return array<int,int[]> List of routes per number of segments
     */
    public function getSegmentCounts(): array
    {
        return $this->segmentCounts;
    }

    /**
     * Returns routes per value of each segment.
     * @return array<int,array<string,array<int,true>>> Routes per value of each segment
     */
    public function getSegmentValues(): array
    {
        return $this->segmentValues;
    }

    /**
     * Returns a route definition by a specific id.
     * @param int $id Id of the route definition
     * @return RouteDefinition The route definition for the specific id
     */
    public function getRouteDefinition(int $id): RouteDefinition
    {
        if (!isset($this->routeDefinitions[$id])) {
            throw new \InvalidArgumentException("Invalid route id '$id'");
        }

        return RouteDefinition::createFromCache($this->routeDefinitions[$id]);
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
