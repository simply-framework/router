<?php

namespace Simply\Router\Compiler\Node;

use Simply\Router\Compiler\CompilerRoute;

/**
 * ResultNode.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ResultNode implements NodeInterface
{
    /** @var CompilerRoute[] */
    private $results;

    public function __construct(int $index = 0)
    {
        $this->results = [];
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getIndex(): int
    {
        throw new \RuntimeException('Cannot get the index of a result node');
    }

    public function getMatchingNodes(): array
    {
        throw new \RuntimeException('Cannot get matching nodes of a result node');
    }

    public function getFallThroughNode(): ?NodeInterface
    {
        throw new \RuntimeException('Cannot get fall-through nodes of a result node');
    }

    public function addRoute(CompilerRoute $route): void
    {
        $this->addSubRoute($route, $route->getPath()->getSegments(), $route->getPath()->getSegments());
    }

    public function addSubRoute(CompilerRoute $route, array $static, array $dynamic): void
    {
        if (\count($static) !== 0 || \count($dynamic) !== 0) {
            throw new \InvalidArgumentException('Unexpected number of segments in the provided route');
        }

        $this->results[] = $route;
    }
}
