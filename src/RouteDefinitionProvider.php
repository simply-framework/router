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
    protected static $segmentCounts;

    protected static $segmentValues;

    protected static $routeDefinitions;

    public function getSegmentCounts(): array
    {
        return static::$segmentCounts;
    }

    public function getSegmentValues(): array
    {
        return static::$segmentValues;
    }

    public function getRouteDefinition(int $id): RouteDefinition
    {
        if (!isset(static::$routeDefinitions[$id])) {
            throw new \InvalidArgumentException("Invalid route id '$id'");
        }

        return new RouteDefinition(static::$routeDefinitions[$id]);
    }
}
