<?php
/**
 * Created by PhpStorm.
 * User: riimu
 * Date: 14/06/2018
 * Time: 16.01
 */

namespace Simply\Router;


class HttpMethod
{
    public const GET = 'GET';
    public const HEAD = 'HEAD';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const CONNECT = 'CONNECT';
    public const OPTIONS = 'OPTIONS';
    public const TRACE = 'TRACE';
    public const PATCH = 'PATCH';

    public static function isValidMethod(string $method): bool
    {
        return \in_array($method, self::getHttpMethods(), true);
    }

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