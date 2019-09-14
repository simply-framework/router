<?php

namespace Simply\Router\Parser\Segment;

/**
 * PlaceholderSegment.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PlaceholderSegment implements SegmentInterface
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNormalizedPath(): string
    {
        return sprintf('{%s}', $this->name);
    }

    public function getNames(): array
    {
        return [$this->name];
    }

    public function getFormat(): string
    {
        return '%s';
    }

    public function isDynamic(): bool
    {
        return true;
    }
}
