<?php

namespace Simply\Router;

/**
 * Represents a route that has been matched.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Route
{
    /** @var RouteDefinition Definition of for the matched route */
    private $definition;

    /** @var string Requested HTTP method*/
    private $method;

    /** @var string[] Segments the requested path */
    private $segments;

    /** @var string[] Values for parameters in the requested route */
    private $values;

    /**
     * Route constructor.
     * @param RouteDefinition $definition The definition of for the matched route
     * @param string $method The requested HTTP method
     * @param string[] $segments The segments the requested path
     * @param string[] $values The values for parameters in the requested route
     */
    public function __construct(RouteDefinition $definition, string $method, array $segments, array $values)
    {
        $this->definition = $definition;
        $this->method = $method;
        $this->segments = $segments;
        $this->values = $values;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns the handler for the requested route
     * @return mixed
     */
    public function getHandler()
    {
        return $this->definition->getHandler();
    }

    public function getPath(): string
    {
        if (\count($this->segments) === 0) {
            return '/';
        }

        $path = sprintf('/%s/',  implode('/', $this->segments));

        if (!$this->definition->hasSlash()) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    public function getParameter(string $name): string
    {
        if (!isset($this->values[$name])) {
            throw new \InvalidArgumentException("Invalid route parameter '$name'");
        }

        return $this->values[$name];
    }
}