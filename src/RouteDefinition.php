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
    /** @var string The name of the route */
    private $name;

    /** @var string[] Allowed HTTP request method for the route */
    private $methods;

    /** @var string[] The static route segments */
    private $segments;

    /** @var string[] PCRE regular expressions for dynamic route segments */
    private $patterns;

    /** @var mixed The handler for the route */
    private $handler;

    /** @var string The format for generating the route from parameters */
    private $format;

    /** @var int[] Associative array of route parameter names and their ordinal numbers */
    private $parameterNames;

    /**
     * RouteDefinition constructor.
     * @param string $name The route name
     * @param string[] $methods The allowed request methods for the route
     * @param string $path The path definition for the route
     * @param mixed $handler The handler for route
     */
    public function __construct(string $name, array $methods, string $path, $handler)
    {
        if (!$this->isConstantValue($handler)) {
            throw new \InvalidArgumentException('Invalid route handler, expected a constant value');
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

        $segments = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($segments as $segment) {
            $this->addSegment($segment);
        }

        if (\count($segments) > 0 && substr($path, -1) !== '/') {
            $this->format = substr($this->format, 0, -1);
        }
    }

    /**
     * Tests if the given value is a constant value.
     * @param mixed $value The value to test
     * @return bool True if the value is a constant value, false if not
     */
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

    private function appendPattern(string $pattern, string $segment, int $start, int $length): string
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
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL);

        try {
            preg_match_all("#$pattern#", '');
            return preg_last_error() === PREG_NO_ERROR;
        } catch(\ErrorException $exception) {
            return false;
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

    /**
     * Returns the route handler.
     * @return mixed The route handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    public function hasSlash(): bool
    {
        return substr($this->format, -1) === '/';
    }

    public function isStatic(): bool
    {
        return \count($this->parameterNames) === 0;
    }

    public function matchPatterns(array $segments, array & $values): bool
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

    public function isMethodAllowed(string $method): bool {
        if (\in_array($method, $this->methods, true)) {
            return true;
        }

        if ($method === HttpMethod::HEAD) {
            return \in_array(HttpMethod::GET, $this->methods, true);
        }

        return false;
    }

    public function formatPath(array $parameters = []): string {
        $values = array_intersect_key($parameters, $this->parameterNames);

        if (\count($values) !== \count($this->parameterNames)) {
            $missingKeys = array_keys(array_diff_key($this->parameterNames, $values));
            throw new \InvalidArgumentException('Missing route parameters: ' . implode(', ', $missingKeys));
        }

        if (\count($parameters) !== \count($values)) {
            $extraKeys = array_keys(array_diff_key($parameters, $this->parameterNames));
            throw new \InvalidArgumentException('Unexpected route parameters: ' . implode(', ', $extraKeys));
        }

        return vsprintf($this->format, array_values(array_merge($this->parameterNames, $values)));
    }
}