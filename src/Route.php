<?php

namespace Simply\Router;

/**
 * Represents a matched request route.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Route
{
    /** @var RouteDefinition Definition of the matched route */
    private $definition;

    /** @var string Requested HTTP method */
    private $method;

    /** @var string[] Segments of the requested path */
    private $segments;

    /** @var string[] Values for the parameters in the requested route */
    private $values;

    /**
     * Route constructor.
     * @param RouteDefinition $definition The definition of the matched route
     * @param string $method The requested HTTP method
     * @param string[] $segments The segments of the requested path
     * @param string[] $values The values for the parameters in the requested route
     */
    public function __construct(RouteDefinition $definition, string $method, array $segments, array $values)
    {
        $this->definition = $definition;
        $this->method = $method;
        $this->segments = $segments;
        $this->values = $values;
    }

    /**
     * Returns the requested HTTP method.
     * @return string The requested HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns the handler for the requested route.
     * @return mixed The handler for the requested route
     */
    public function getHandler()
    {
        return $this->definition->getHandler();
    }

    /**
     * Returns the unencoded canonical path for the requested route.
     * @return string The canonical path for the requested route
     */
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

    /**
     * Returns the encoded URL for the requested route.
     * @return string The encoded URL for the requested route.
     */
    public function getUrl(): string
    {
        return $this->definition->formatUrl($this->values);
    }

    /**
     * Returns the value for the given parameter from the requested route.
     * @param string $name The name of the parameter
     * @return string The value for the parameter
     */
    public function getParameter(string $name): string
    {
        if (!isset($this->values[$name])) {
            throw new \InvalidArgumentException("Invalid route parameter '$name'");
        }

        return $this->values[$name];
    }
}