<?php

namespace Simply\Router\Collector;

use Simply\Router\DispatcherInterface;

/**
 * CollectedRoute.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CollectedRoute
{
    private $methods;
    private $path;
    private $handler;
    private $name;

    public function __construct(array $methods, string $path, $handler, ?string $name)
    {
        $this->methods = array_values(array_intersect(DispatcherInterface::HTTP_METHODS, $methods));

        if (count($this->methods) !== count($methods)) {
            throw new \InvalidArgumentException('Invalid list of HTTP methods: ' . implode(', ', $methods));
        }

        $this->path = $path;
        $this->handler = $handler;
        $this->name = $name;

        if (!$this->isConstantValue($handler)) {
            throw new \InvalidArgumentException('Invalid handler, the handler must be a static value');
        }
    }

    private function isConstantValue($value): bool
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isConstantValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return $value === null || is_scalar($value);
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
