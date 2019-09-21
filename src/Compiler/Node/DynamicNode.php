<?php

namespace Simply\Router\Compiler\Node;

use Simply\Router\Compiler\CompilerRoute;
use Simply\Router\Parser\Segment\DynamicSegment;

/**
 * DynamicNode.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DynamicNode implements NodeInterface
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
        throw new \RuntimeException('Node path cannot start from a dynamic node');
    }

    public function addSubRoute(CompilerRoute $route, array $static, array $dynamic): void
    {
        if (\count($dynamic) < 1) {
            throw new \InvalidArgumentException('Unexpected number of dynamic segments in the provided route');
        }

        $segment = array_shift($dynamic);
        $matcher = $segment instanceof DynamicSegment ? $segment->getPattern() : null;
        $next = $dynamic === [] ? ResultNode::class : self::class;

        $node = $matcher === null
            ? $this->getSubNode($this->fallThrough, $next)
            : $this->getSubNode($this->matching[$matcher], $next);

        $node->addSubRoute($route, $static, $dynamic);
    }

    private function getSubNode(& $variable, string $type): NodeInterface
    {
        if (!isset($variable)) {
            if ($type === self::class) {
                $variable = new self($this->index + 1);
            } else {
                $variable = new ResultNode();
            }
        }

        return $variable;
    }
}
