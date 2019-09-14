<?php

namespace Simply\Router;

/**
 * AbstractDispatcher.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractDispatcher implements DispatcherInterface
{
    protected function dispatchMiss(string $method, string $path): array
    {
        static $recursion = false;

        if ($recursion) {
            return [self::NOT_FOUND];
        }

        if ($method === 'HEAD') {
            return $this->dispatch('GET', $path);
        }

        try {
            $recursion = true;
            return $this->findAllowedMethods($method, $path);
        } finally {
            $recursion = false;
        }
    }

    private function findAllowedMethods(string $method, string $path): array
    {
        if (!\in_array($method, self::HTTP_METHODS, true)) {
            throw new \RuntimeException("Invalid HTTP Method: $method");
        }

        $allowedMethods = [];

        foreach (array_diff(self::HTTP_METHODS, [$method, 'HEAD']) as $allowed) {
            if ($allowed === $method || $allowed === 'HEAD') {
                continue;
            }

            if ($this->dispatch($allowed, $path)[0] === self::FOUND) {
                $allowedMethods[] = $allowed;

                if ($allowed === 'GET') {
                    $allowedMethods[] = 'HEAD';
                }
            }
        }

        if ($allowedMethods !== []) {
            return [self::METHOD_NOT_ALLOWED, $allowedMethods];
        }

        return [self::NOT_FOUND];
    }

    public function format(string $name, array $parameters = []): string
    {
        /** @var string[] $parameterNames */
        [$format, $parameterNames] = $this->getFormat($name);

        if ($format === null) {
            throw new \InvalidArgumentException("Undefined route '$name'");
        }

        $orderedParameters = [];

        foreach ($parameterNames as $parameter) {
            if (!isset($parameters[$parameter])) {
                $missingParameters = array_diff($parameterNames, array_keys($parameters));
                throw new \InvalidArgumentException("Missing parameters for route '$name': $missingParameters");
            }

            $orderedParameters[] = rawurlencode($parameters[$parameter]);
        }

        if (\count($orderedParameters) !== \count($parameters)) {
            $invalidParameters = array_diff(array_keys($parameters), $parameterNames);
            throw new \InvalidArgumentException("Invalid parameters for route '$name': $invalidParameters");
        }

        return vsprintf($format, $orderedParameters);
    }
}
