<?php

namespace Simply\Router\Compiler\Template;

/**
 * DispatcherTemplateInterface.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface DispatcherTemplateInterface
{
    public function formatDispatcher(string $class, array $routes, array $staticPaths, array $methodCases): string;
    public function formatCountSwitch(array $cases): string;
    public function formatNode(int $index, array $staticRoutes, array $dynamicRoutes, ?string $placeholder): string;
    public function formatRoutes(array $routes): string;
}
