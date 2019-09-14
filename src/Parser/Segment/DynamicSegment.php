<?php

namespace Simply\Router\Parser\Segment;

/**
 * DynamicSegment.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DynamicSegment implements SegmentInterface
{
    private $names;
    private $pattern;
    private $format;

    public function __construct(array $names, string $pattern, string $format)
    {
        $this->names = (static function (string ... $names): array {
            return $names;
        })(... $names);
        $this->pattern = $pattern;
        $this->format = $format;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getNormalizedPath(): string
    {
        return sprintf('#%s#', substr($this->pattern, 1, -1));
    }

    public function getNames(): array
    {
        return $this->names;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function isDynamic(): bool
    {
        return true;
    }
}
