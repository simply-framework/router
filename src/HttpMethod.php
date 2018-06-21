<?php

namespace Simply\Router;

/**
 * Provides valid values for the HTTP request method.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class HttpMethod
{
    /** @var string The HTTP GET request method */
    public const GET = 'GET';

    /** @var string The HTTP HEAD request method */
    public const HEAD = 'HEAD';

    /** @var string The HTTP POST request method */
    public const POST = 'POST';

    /** @var string The HTTP PUT request method */
    public const PUT = 'PUT';

    /** @var string The HTTP DELETE request method */
    public const DELETE = 'DELETE';

    /** @var string The HTTP CONNECT request method */
    public const CONNECT = 'CONNECT';

    /** @var string The HTTP OPTIONS request method */
    public const OPTIONS = 'OPTIONS';

    /** @var string The HTTP TRACE request method */
    public const TRACE = 'TRACE';

    /** @var string The HTTP PATCH request method */
    public const PATCH = 'PATCH';

    /**
     * Tells if the given string is a valid HTTP request method.
     * @param string $method The string to test
     * @return bool True if it is a valid HTTP request method, false if not
     */
    public static function isValidMethod(string $method): bool
    {
        return \in_array($method, self::getHttpMethods(), true);
    }

    /**
     * Returns list of all valid HTTP request methods.
     * @return array List of all valid HTTP request methods
     */
    public static function getHttpMethods(): array
    {
        return [
            self::GET,
            self::HEAD,
            self::POST,
            self::PUT,
            self::DELETE,
            self::CONNECT,
            self::OPTIONS,
            self::TRACE,
            self::PATCH,
        ];
    }
}
