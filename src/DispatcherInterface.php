<?php

namespace Simply\Router;

/**
 * Dispatcher.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface DispatcherInterface
{
    public const HTTP_METHODS = [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PATCH',
        'PURGE',
    ];

    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    public function dispatch(string $method, string $path): array;

    public function format(string $name, array $parameters = []): string;

    /**
     * @param string $name
     * @return array
     */
    public function getFormat(string $name): array;
}
