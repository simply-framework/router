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
    private $name;
    private $methods;
    private $segments;
    private $patterns;
    private $handler;
    private $format;
    private $parameterNames;

    public function __construct(string $name, array $methods, string $path, $handler)
    {
        if (!$this->isConstantValue($handler)) {
            throw new \InvalidArgumentException("Invalid route handler, except a constant value");
        }

        $this->name = $name;
        $this->methods = [];
        $this->segments = [];
        $this->patterns = [];
        $this->handler = $handler;
        $this->format = '/';
        $this->parameterNames = [];

        foreach ($methods as $method) {
            $this->addMethod($method);
        }

        $segments = array_filter(explode('/', $path), function (string $segment): bool {
            return \strlen($segment) > 0;
        });

        foreach ($segments as $segment) {
            $this->addSegment($segment);
        }

        if (count($segments) > 0 && substr($path, -1) !== '/') {
            $this->format = substr($this->format, 0, -1);
        }
    }

    private function isConstantValue($value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (\is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isConstantValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function addMethod(string $method): void
    {
        if (!HttpMethod::isValidMethod($method)) {
            throw new \InvalidArgumentException("Invalid HTTP request method '$method'");
        }

        $this->methods[] = $method;
    }

    private function addSegment(string $segment): void
    {
        if (strpos($segment, '#') !== false) {
            throw new \InvalidArgumentException("Invalid character '#' in route definition path");
        }

        $count = preg_match_all(
            "/\{(?'name'[a-z0-9_]++)(?::(?'pattern'(?:[^{}]++|\{(?&pattern)\})++))?\}/i",
            $segment,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL
        );

        if ($count === 0) {
            $this->segments[] = $segment;
            $this->format .= str_replace('%', '%%', $segment) . '/';
            return;
        }

        $pattern = $this->formatPattern($segment, $matches);
        $this->patterns[\count($this->segments)] = sprintf('#%s#', $pattern);
        $this->segments[] = '#';
    }

    private function formatPattern(string $segment, array $matches): string
    {
        $fullPattern = $this->appendPattern('', $segment, 0, $matches[0][0][1]);

        foreach ($matches as $i => $match) {
            $name = $match['name'][0];
            $pattern = $match['pattern'][0] ?? null;

            $this->format .= '%s';

            if (isset($this->parameterNames[$name])) {
                throw new \InvalidArgumentException("Duplicate parameter name '$name'");
            }

            $this->parameterNames[$name] = \count($this->parameterNames);

            if ($pattern !== null) {
                if (!$this->isValidPattern($pattern)) {
                    throw new \InvalidArgumentException("Error compiling pattern '$pattern'");
                }

                $fullPattern .= sprintf("(?'%s'%s)", $name, $pattern);
            } else {
                $fullPattern .= sprintf("(?'%s'.*)", $name);
            }

            $start = $match[0][1] + \strlen($match[0][0]);
            $length = (isset($matches[$i + 1]) ? $matches[$i + 1][0][1] : \strlen($segment)) - $start;
            $fullPattern = $this->appendPattern($fullPattern, $segment, $start, $length);
        }

        $this->format .= '/';

        return $fullPattern;
    }

    private function appendPattern(string $pattern, string $segment, int $start, int $length)
    {
        if ($length < 1) {
            return $pattern;
        }

        $constant = substr($segment, $start, $length);
        $this->format .= str_replace('%', '%%', $constant);
        return $pattern . preg_quote($constant, '#');
    }

    private function isValidPattern(string $pattern): bool
    {
        $error = false;

        set_error_handler(function () use (& $error) {
            $error = true;
        }, E_ALL);

        try {
            preg_match_all("#$pattern#", '');
            return $error === false && preg_last_error() === PREG_NO_ERROR;
        } finally {
            restore_error_handler();
        }
    }

    public static function createFromCache(array $cache): RouteDefinition
    {
        /** @var RouteDefinition $definition */
        $definition = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();

        [
            $definition->name,
            $definition->methods,
            $definition->segments,
            $definition->patterns,
            $definition->handler,
            $definition->format,
            $definition->parameterNames,
        ] = $cache;

        $definition->parameterNames = array_flip($definition->parameterNames);

        return $definition;
    }

    public function getDefinitionCache(): array
    {
        return [
            $this->name,
            $this->methods,
            $this->segments,
            $this->patterns,
            $this->handler,
            $this->format,
            array_keys($this->parameterNames),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function hasSlash(): bool
    {
        return substr($this->format, -1) === '/';
    }

    public function matchPatterns(array $segments, & $values): bool
    {
        $parsed = [];

        foreach ($this->patterns as $i => $pattern) {
            if (!preg_match($pattern, $segments[$i], $match)) {
                return false;
            }

            if ($match[0] !== $segments[$i]) {
                return false;
            }

            $parsed += array_intersect_key($match, $this->parameterNames);
        }

        $values = $parsed;

        return true;
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

    public function formatPath(array $parameters = []) {
        $values = array_intersect_key($parameters, $this->parameterNames);

        if (\count($values) !== \count($this->parameterNames)) {
            $missingKeys = array_keys(array_diff_key($this->parameterNames, $values));
            throw new \InvalidArgumentException('Missing route parameters: ' . implode(', ', $missingKeys));
        }
        if (\count($parameters) !== \count($values)) {
            $extraKeys = array_keys(array_diff_key($parameters, $this->parameterNames));
            throw new \InvalidArgumentException(sprintf('Unexpected route parameters: %s',implode(', ', $extraKeys)));
        }

        return vsprintf($this->format, array_values(array_merge($this->parameterNames, $values)));
    }
}