<?php

namespace Simply\Router\Compiler;

use Simply\Router\Collector\CollectedRoute;
use Simply\Router\Parser\ParsedPath;
use Simply\Router\Parser\Segment\DynamicSegment;
use Simply\Router\Parser\Segment\PlaceholderSegment;
use Simply\Router\Parser\Segment\SegmentInterface;
use Simply\Router\Parser\Segment\StaticSegment;

/**
 * RouteNode.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteNode
{
    private $routes;

    /** @var RouteNode[] */
    private $staticNodes;

    /** @var RouteNode[] */
    private $dynamicNodes;

    /** @var RouteNode */
    private $placeholderNode;

    public function __construct()
    {
        $this->routes = [];
        $this->staticNodes = [];
        $this->dynamicNodes = [];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getStaticNodes(): array
    {
        return $this->staticNodes;
    }

    public function getDynamicNodes(): array
    {
        return $this->dynamicNodes;
    }

    public function getPlaceholderNode(): ?RouteNode
    {
        return $this->placeholderNode;
    }

    public function addRoute(CompilerRoute $route): void
    {
        $segments = $route->getPath()->getSegments();
        $segment = array_shift($segments);

        if ($segment === null) {
            $this->routes[] = $route;
        } else {
            $this->getSegmentNode($segment)->addSubRoute($route, $segments);
        }
    }

    private function addSubRoute(CompilerRoute $route, array $remainingSegments): void
    {
        if ($remainingSegments === []) {
            $this->routes[] = $route;
        } else {
            $this->getSegmentNode(array_shift($remainingSegments))->addSubRoute($route, $remainingSegments);
        }
    }

    private function getSegmentNode(SegmentInterface $segment): RouteNode
    {
        switch (true) {
            case $segment instanceof StaticSegment:
                return $this->getSubNode($this->staticNodes[$segment->getSegment()]);
            case $segment instanceof DynamicSegment:
                return $this->getSubNode($this->dynamicNodes[$segment->getPattern()]);
            default:
                return $this->getSubNode($this->placeholderNode);
        }
    }

    private function getSubNode(& $variable): RouteNode
    {
        if (!isset($variable)) {
            $variable = new RouteNode();
        }

        return $variable;
    }
}
