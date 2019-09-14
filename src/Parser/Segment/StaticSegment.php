<?php

namespace Simply\Router\Parser\Segment;

/**
 * StaticSegment.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StaticSegment implements SegmentInterface
{
    private $segment;
    private $format;

    public function __construct(string $segment, string $format)
    {
        $this->segment = $segment;
        $this->format = $format;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function getNormalizedPath(): string
    {
        return $this->segment;
    }

    public function getNames(): array
    {
        return [];
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function isDynamic(): bool
    {
        return false;
    }
}
