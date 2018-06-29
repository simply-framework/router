<?php

namespace Simply\Router;

/**
 * Definitions for a specific route.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteDefinition
{
    /** Value used to indicate a segment is dynamic rather than static */
    public const DYNAMIC_SEGMENT = '/';

    /** @var string The name of the route */
    private $name;

    /** @var string[] Allowed HTTP request methods for the route */
    private $methods;

    /** @var string[] The static route segments */
    private $segments;

    /** @var string[] PCRE regular expressions for dynamic route segments */
    private $patterns;

    /** @var mixed The handler for the route */
    private $handler;

    /** @var string The format for generating the route URL from parameters */
    private $format;

    /** @var string[] Names of route parameters in order of appearance */
    private $parameterNames;

    /**
     * RouteDefinition constructor.
     * @param string $name Name of the route
     * @param string[] $methods Allowed HTTP request methods for the route
     * @param string $path Path definition for the route
     * @param mixed $handler Handler for route
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

        $segments = split_segments($path);

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

    /**
     * Adds a method to the list of allowed HTTP request methods.
     * @param string $method The HTTP request method to add
     */
    private function addMethod(string $method): void
    {
        if (!HttpMethod::isValid($method)) {
            throw new \InvalidArgumentException("Invalid HTTP request method '$method'");
        }

        $this->methods[] = $method;
    }

    /**
     * Appends a path segment to the list of matched path segments for the route.
     * @param string $segment The segment to add
     */
    private function addSegment(string $segment): void
    {
        preg_match_all(
            "/\{(?'name'[a-z0-9_]++)(?::(?'pattern'(?:[^{}]++|\{(?&pattern)\})++))?\}/i",
            $segment,
            $matches,
            \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL
        );

        if (empty($matches)) {
            $this->segments[] = $segment;
            $this->format .= $this->formatEncode($segment) . '/';
            return;
        }

        $pattern = $this->formatPattern($segment, $matches);
        $this->patterns[\count($this->segments)] = sprintf('/%s/', $pattern);
        $this->segments[] = self::DYNAMIC_SEGMENT;
    }

    /**
     * Creates a dynamic segment regular expression based on the provided segment.
     * @param string $segment The segment to turn into regular expression
     * @param array[] $matches List of matches for the dynamic parts
     * @return string The fully formed regular expression for the segment
     */
    private function formatPattern(string $segment, array $matches): string
    {
        $fullPattern = $this->appendPattern('', $segment, 0, $matches[0][0][1]);

        foreach ($matches as $i => $match) {
            $name = $match['name'][0];
            $pattern = $match['pattern'][0] ?? '.*';

            $this->format .= '%s';

            if (\in_array($name, $this->parameterNames, true)) {
                throw new \InvalidArgumentException("Duplicate parameter name '$name'");
            }

            if (!$this->isValidPattern($pattern)) {
                throw new \InvalidArgumentException("Invalid regular expression '$pattern'");
            }

            $this->parameterNames[] = $name;
            $fullPattern .= sprintf("(?'%s'%s)", $name, $pattern);

            $start = $match[0][1] + \strlen($match[0][0]);
            $length = ($matches[$i + 1][0][1] ?? \strlen($segment)) - $start;
            $fullPattern = $this->appendPattern($fullPattern, $segment, $start, $length);
        }

        $this->format .= '/';

        return $fullPattern;
    }

    /**
     * Appends a static section to the pattern from the given segment.
     * @param string $pattern The pattern to append
     * @param string $segment The full segment to copy
     * @param int $start The start of the static section
     * @param int $length The length of the static section
     * @return string The pattern with the static section appended
     */
    private function appendPattern(string $pattern, string $segment, int $start, int $length): string
    {
        if ($length < 1) {
            return $pattern;
        }

        $constant = substr($segment, $start, $length);
        $this->format .= $this->formatEncode($constant);
        return $pattern . preg_quote($constant, '/');
    }

    /**
     * URL encodes a string to be part of the URL format string.
     * @param string $part The part to encode
     * @return string The encoded part
     */
    private function formatEncode(string $part): string
    {
        return str_replace('%', '%%', rawurlencode($part));
    }

    /**
     * Tells if the given string is a valid regular expression without delimiters.
     * @param string $pattern The string to test
     * @return bool True if it is a valid PCRE regular expression, false if not
     */
    private function isValidPattern(string $pattern): bool
    {
        $result = false;
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, \E_ALL);

        try {
            $result = preg_match("/$pattern/", '');
        } catch (\ErrorException $exception) {
            $errorMessage = sprintf("Invalid regular expression '%s': %s", $pattern, $exception->getMessage());
            throw new \InvalidArgumentException($errorMessage, 0, $exception);
        } finally {
            restore_error_handler();
        }

        return $result !== false && preg_last_error() === \PREG_NO_ERROR;
    }

    /**
     * Returns a new RouteDefinition instance based on the cached values.
     * @param array $cache The cached RouteDefinition values
     * @return self A new RouteDefinition instance
     */
    public static function createFromCache(array $cache): self
    {
        /** @var self $definition */
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

        return $definition;
    }

    /**
     * Returns cached values for the RouteDefinition that can be used to instantiate a new RouteDefinition.
     * @return array RouteDefinition cache values
     */
    public function getDefinitionCache(): array
    {
        return [
            $this->name,
            $this->methods,
            $this->segments,
            $this->patterns,
            $this->handler,
            $this->format,
            $this->parameterNames,
        ];
    }

    /**
     * Returns the name of the route.
     * @return string The name of the route
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the allowed methods for the route.
     * @return string[] The allowed methods for the route
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Returns the static segments for the route.
     * @return string[] The static segments for the route
     */
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

    /**
     * Tells if the canonical route path ends in a forward slash or not.
     * @return bool True if the path ends in a slash, false if not
     */
    public function hasSlash(): bool
    {
        return substr($this->format, -1) === '/';
    }

    /**
     * Tells if the path is completely static without any dynamic segments.
     * @return bool True if the path is static, false if not
     */
    public function isStatic(): bool
    {
        return \count($this->parameterNames) === 0;
    }

    /**
     * Matches the given segments against the dynamic path segments.
     * @param string[] $segments The segments to match against
     * @param string[] $values Array that will be populated with route parameter values on match
     * @return bool True if the dynamic segments match, false if not
     */
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

            $parsed += array_intersect_key($match, array_flip($this->parameterNames));
        }

        $values = $parsed;

        return true;
    }

    /**
     * Tells if the given HTTP request method is allowed by the route.
     * @param string $method The HTTP request method to test
     * @return bool True if the given HTTP request method is allowed, false if not
     */
    public function isMethodAllowed(string $method): bool
    {
        if (\in_array($method, $this->methods, true)) {
            return true;
        }

        if ($method === HttpMethod::HEAD) {
            return \in_array(HttpMethod::GET, $this->methods, true);
        }

        return false;
    }

    /**
     * Returns an encoded URL for the route based on the given parameter values.
     * @param string[] $parameters Values for the route parameters
     * @return string The encoded URL for the route
     */
    public function formatUrl(array $parameters = []): string
    {
        $values = [];

        foreach ($this->parameterNames as $name) {
            if (!isset($parameters[$name])) {
                throw new \InvalidArgumentException("Missing route parameter '$name'");
            }

            $values[] = rawurlencode($parameters[$name]);
            unset($parameters[$name]);
        }

        if (!empty($parameters)) {
            throw new \InvalidArgumentException(
                'Unexpected route parameters: ' . implode(', ', array_keys($parameters))
            );
        }

        return vsprintf($this->format, $values);
    }
}
