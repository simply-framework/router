<?php

namespace Simply\Router\Compiler;

use Simply\Router\Parser\Segment\DynamicSegment;
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
    private $static;
    private $index;
    private $routes;

    /** @var RouteNode[] */
    private $matchNodes;

    /** @var RouteNode */
    private $skipNode;

    public function __construct(int $index = 0, bool $static = true)
    {
        $this->static = $static;
        $this->index = $index;
        $this->routes = [];
        $this->matchNodes = [];
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getMatchNodes(): array
    {
        return $this->matchNodes;
    }

    public function getSkipNode(): ?self
    {
        return $this->skipNode;
    }

    public function addRoute(CompilerRoute $route): void
    {
        $static = $route->getPath()->getSegments();
        $dynamic = $route->getPath()->isStaticPath() ? [] : $static;
        $segment = array_shift($static);

        if ($segment === null) {
            $this->routes[] = $route;
        } else {
            $this->getNextNode($segment, $static !== [])->addSubRoute($route, $static, $dynamic);
        }
    }

    private function addSubRoute(CompilerRoute $route, array $static, array $dynamic): void
    {
        if ($static === [] && $dynamic === []) {
            $this->routes[] = $route;
            return;
        }

        $segment = $this->static ? array_shift($static) : array_shift($dynamic);
        $node = $this->getNextNode($segment, $static !== []);

        $node->addSubRoute($route, $static, $dynamic);
    }

    private function getNextNode(?SegmentInterface $segment, bool $static): self
    {
        $matcher = null;

        if ($segment instanceof StaticSegment && $this->static) {
            $matcher = $segment->getSegment();
        } elseif ($segment instanceof DynamicSegment && !$this->static) {
            $matcher = $segment->getPattern();
        }

        return $matcher === null
            ? $this->getSubNode($this->skipNode, $static)
            : $this->getSubNode($this->matchNodes[$matcher], $static);
    }

    private function getSubNode(& $variable, bool $static): self
    {
        if (!isset($variable)) {
            $nextIndex = $this->static === $static ? $this->index + 1 : 0;
            $variable = new self($nextIndex, $static);
        }

        return $variable;
    }
}
