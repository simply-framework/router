<?php

namespace Simply\Router;

/**
 * RouteDefinitionProvider.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteDefinitionProvider
{
    protected $staticRoutes = [];

    protected $segmentCounts = [];

    protected $segmentValues = [];

    protected $routeDefinitions = [];

    protected $routesByName = [];

    public function addRouteDefinition(RouteDefinition $definition)
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
                $this->segmentValues[$i][$segment][] = $id;
            }

            $this->segmentCounts[\count($segments)][] = $id;
        }
    }

    public function getCacheFile(): string
    {
        return strtr(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'cache_template.php'), [
            "['STATICS']" => var_export($this->staticRoutes, true),
            "['COUNTS']" => var_export($this->segmentCounts, true),
            "['VALUES']" => var_export($this->segmentValues, true),
            "['DEFINITIONS']" => var_export($this->routeDefinitions, true),
            "['NAMES']" => var_export($this->routesByName, true),
        ]);
    }

    public function getStaticRoutes(): array
    {
        return $this->staticRoutes;
    }

    public function getSegmentCounts(): array
    {
        return $this->segmentCounts;
    }

    public function getSegmentValues(): array
    {
        return $this->segmentValues;
    }

    public function getRouteDefinition(int $id): RouteDefinition
    {
        if (!isset($this->routeDefinitions[$id])) {
            throw new \InvalidArgumentException("Invalid route id '$id'");
        }

        return RouteDefinition::createFromCache($this->routeDefinitions[$id]);
    }

    public function getRouteDefinitionByName(string $name): RouteDefinition
    {
        if (!isset($this->routesByName[$name])) {
            throw new \InvalidArgumentException("Invalid route name '$name'");
        }

        return $this->getRouteDefinition($this->routesByName[$name]);
    }
}
