<?php

namespace Simply\Router\Compiler\Node;

use Simply\Router\Compiler\CompilerRoute;
use Simply\Router\Parser\Segment\StaticSegment;

/**
 * StaticNode.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StaticNode implements NodeInterface
{
    private $index;

    /** @var NodeInterface[] */
    private $matching;

    /** @var NodeInterface */
    private $fallThrough;

    public function __construct(int $index = 0)
    {
        $this->index = $index;
        $this->matching = [];
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getMatchingNodes(): array
    {
        return $this->matching;
    }

    public function getFallThroughNode(): ?NodeInterface
    {
        return $this->fallThrough;
    }

    public function addRoute(CompilerRoute $route): void
    {
        $static = $route->getPath()->getSegments();
        $dynamic = $route->getPath()->isStaticPath() ? [] : $static;
        $this->addSubRoute($route, $static, $dynamic);
    }

    public function addSubRoute(CompilerRoute $route, array $static, array $dynamic): void
    {
        if (count($static) < 1) {
            throw new \InvalidArgumentException('Unexpected number of static segments in the provided route');
        }

        $segment = array_shift($static);
        $matcher = $segment instanceof StaticSegment ? $segment->getSegment() : null;
        $next = self::class;

        if ($static === []) {
            $next = $dynamic === [] ? ResultNode::class : DynamicNode::class;
        }

        $node = $matcher === null
            ? $this->getSubNode($this->fallThrough, $next)
            : $this->getSubNode($this->matching[$matcher], $next);

        $node->addSubRoute($route, $static, $dynamic);
    }

    private function getSubNode(& $variable, string $type): NodeInterface
    {
        if (!isset($variable)) {
            if ($type === self::class) {
                $variable = new StaticNode($this->index + 1);
            } elseif ($type === DynamicNode::class) {
                $variable = new DynamicNode();
            } else {
                $variable = new ResultNode();
            }
        }

        return $variable;
    }
}
