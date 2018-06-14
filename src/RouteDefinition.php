<?php
/**
 * Created by PhpStorm.
 * User: riimu
 * Date: 14/06/2018
 * Time: 15.07
 */

namespace Simply\Router;


class RouteDefinition
{
    private $patterns;

    private $methods;

    public function __construct(array $definition)
    {
        (function (string ... $patterns) {
            $this->patterns = $patterns;
        })($definition['patterns'] ?? []);
    }

    public function getAllowedMethods(): array
    {
        return $this->methods;
    }

    public function matchPatterns(array $segments, & $values): bool
    {
        $parsed = [];

        foreach ($this->patterns as $i => $pattern) {
            if (!preg_match($pattern, $segments[$i], $matches)) {
                return false;
            }

            if ($matches[0] !== $segments[$i]) {
                return false;
            }

            $parsed += array_filter($matches, '\is_string', ARRAY_FILTER_USE_KEY);
        }

        $values = $parsed;
    }

    public function isMethodAllowed(string $method) {
        if (\in_array($method, $this->methods, true)) {
            return true;
        }

        if ($method === HttpMethod::HEAD) {
            return \in_array(HttpMethod::GET, $this->methods, true);
        }

        return false;
    }
}