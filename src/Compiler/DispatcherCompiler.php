<?php

namespace Simply\Router\Compiler;

use Simply\Router\Collector\CollectedRoute;
use Simply\Router\Collector\RouteCollector;
use Simply\Router\Compiler\Template\DispatcherTemplateInterface;
use Simply\Router\Compiler\Template\SingleRouteDispatcherTemplate;
use Simply\Router\Parser\PathParser;

/**
 * DispatcherCompiler.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DispatcherCompiler
{
    /** @var DispatcherTemplateInterface */
    private $template;

    /** @var PathParser */
    private $parser;

    public function __construct()
    {
        $this->template = new SingleRouteDispatcherTemplate();
        $this->parser = new PathParser();
    }

    public function compile(RouteCollector $collector, string $class = \CompiledDispatcher::class): string
    {
        if (!$this->isValidClassName($class)) {
            throw new \InvalidArgumentException("Invalid class name: $class");
        }

        $routes = $this->getCompilerRoutes($collector);
        $staticPaths = $this->mapStaticPaths($routes);
        $routeNodes = $this->mapRouteNodes($routes);

        $methodCases = $this->compileMethodCases($routeNodes);

        return $this->template->formatDispatcher($class, $routes, $staticPaths, $methodCases);
    }

    private function isValidClassName($class): bool
    {
        return preg_match('/^([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)(\\\\(?1))*$/', $class) === 1;
    }

    /**
     * @param RouteCollector $collector
     * @return CompilerRoute[]
     */
    private function getCompilerRoutes(RouteCollector $collector): array
    {
        $collectedRoutes = $collector->getCollectedRoutes();
        $compilerRoutes = [];

        $reservedNames = array_reduce($collectedRoutes, static function (array $carry, CollectedRoute $route) {
            return $route->getName() === null
                ? $carry
                : $carry + [$route->getName() => true];
        }, []);

        $nextId = static function () use ($reservedNames): string {
            static $idCandidate = 'a';

            while (isset($reservedNames[$idCandidate])) {
                $idCandidate++;
            }

            return $idCandidate++;
        };

        foreach ($collectedRoutes as $route) {
            $id = $route->getName() ?? $nextId();

            if (isset($compilerRoutes[$id])) {
                throw new \InvalidArgumentException("Cannot have multiple routes with the same name '$id'");
            }

            $compilerRoutes[$id] = new CompilerRoute($id, $route, $this->parser->parse($route->getPath()));
        }

        return $compilerRoutes;
    }

    /**
     * @param CompilerRoute[] $routes
     * @return CompilerRoute[][][]
     */
    private function mapStaticPaths(array $routes): array
    {
        $staticPaths = [];

        foreach ($routes as $route) {
            if ($route->getPath()->isStaticPath()) {
                $staticPath = $route->getPath()->getStaticPath();

                foreach ($route->getRoute()->getMethods() as $method) {
                    $staticPaths[$method][$staticPath][] = $route;
                }
            }
        }

        return $staticPaths;
    }

    /**
     * @param CompilerRoute[] $routes
     * @return RouteNode[][]
     */
    private function mapRouteNodes(array $routes): array
    {
        /** @var RouteNode[][] $routeNodes */
        $routeNodes = [];

        foreach ($routes as $route) {
            $segments = $route->getPath()->getSegments();
            $count = count($segments);

            foreach ($route->getRoute()->getMethods() as $method) {
                if (!isset($routeNodes[$method][$count])) {
                    $routeNodes[$method][$count] = new RouteNode();
                }

                $routeNodes[$method][$count]->addRoute($route);
            }
        }

        return $routeNodes;
    }

    /**
     * @param RouteNode[][] $routeNodes
     * @return string[]
     */
    private function compileMethodCases(array $routeNodes): array
    {
        $methodCases = [];

        foreach ($routeNodes as $method => $routesByMethod) {
            $countCases = [];

            foreach ($routesByMethod as $count => $routeNode) {
                $countCases[$count] = $this->compileRouteNode($routeNode);
            }

            $methodCases[$method] = $this->template->formatCountSwitch($countCases);
        }

        return $methodCases;
    }

    private function compileRouteNode(RouteNode $node): string
    {
        $routes = $node->getRoutes();

        if ($routes) {
            return $this->template->formatRoutes($routes);
        }

        $matchNodes = array_map([$this, 'compileRouteNode'], $node->getMatchNodes());
        $skipNode = $node->getSkipNode() ? $this->compileRouteNode($node->getSkipNode()) : null;

        return $node->isStatic()
            ? $this->template->formatStaticNode($node->getIndex(), $matchNodes, $skipNode)
            : $this->template->formatDynamicNode($node->getIndex(), $matchNodes, $skipNode);
    }
}
