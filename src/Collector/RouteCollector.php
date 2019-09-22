<?php

namespace Simply\Router\Collector;

use Simply\Router\Parser\OptionalParser;

/**
 * RouteCollector.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteCollector
{
    /** @var CollectedRoute[] */
    private $routes;

    private $optionalParser;

    public function __construct()
    {
        $this->optionalParser = new OptionalParser();
    }

    /**
     * @return CollectedRoute[]
     */
    public function getCollectedRoutes(): array
    {
        return $this->routes;
    }

    public function request($method, string $path, $handler, string $name = null): self
    {
        foreach ($this->optionalParser->parseOptionalPaths($path) as $fork) {
            $this->routes[] = new CollectedRoute(\is_array($method) ? $method : [$method], $fork, $handler, $name);
            $name = null;
        }

        return $this;
    }
}
