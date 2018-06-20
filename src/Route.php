<?php
/**
 * Created by PhpStorm.
 * User: riimu
 * Date: 15/06/2018
 * Time: 10.12
 */

namespace Simply\Router;


class Route
{
    /** @var RouteDefinition The definition for the matched route */
    private $definition;

    /** @var string The requested HTTP method */
    private $method;

    /** @var string[] Actual segments in the requested route */
    private $segments;

    /** @var string[] Values for parameters in the requested route */
    private $values;

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