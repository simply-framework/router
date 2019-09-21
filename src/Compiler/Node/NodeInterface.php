<?php

namespace Simply\Router\Compiler\Node;

use Simply\Router\Compiler\CompilerRoute;

/**
 * NodeInterface.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface NodeInterface
{
    public function getIndex(): int;

    public function getMatchingNodes(): array;

    public function getFallThroughNode(): ?self;

    public function addRoute(CompilerRoute $route): void;

    public function addSubRoute(CompilerRoute $route, array $static, array $dynamic): void;
}
