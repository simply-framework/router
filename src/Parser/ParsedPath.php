<?php

namespace Simply\Router\Parser;

use Simply\Router\Parser\Segment\SegmentInterface;
use Simply\Router\Parser\Segment\StaticSegment;

/**
 * ParsedRoute.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ParsedPath
{
    /** @var SegmentInterface[] */
    private $segments;
    private $hasCanonicalSlash;

    public function __construct(array $segments, bool $hasCanonicalSlash)
    {
        $this->segments = (static function (SegmentInterface ... $segments) {
            return $segments;
        })(... $segments);

        $this->hasCanonicalSlash = $hasCanonicalSlash;
    }

    public function isStaticPath(): bool
    {
        foreach ($this->segments as $segment) {
            if ($segment->isDynamic()) {
                return false;
            }
        }

        return true;
    }

    public function getStaticPath(): string
    {
        return $this->segmentsWithSlash(array_map(static function (StaticSegment $segment): string {
            return $segment->getSegment();
        }, $this->segments));
    }

    public function getFormat(): string
    {
        return $this->segmentsWithSlash(array_map(static function (SegmentInterface $segment): string {
            return $segment->getFormat();
        }, $this->segments));
    }

    public function getParameterNames(): array
    {
        return array_reduce($this->segments, static function (array $carry, SegmentInterface $segment): array {
            return array_merge($carry, $segment->getNames());
        }, []);
    }

    private function segmentsWithSlash(array $segments): string
    {
        return sprintf($this->hasCanonicalSlash ? '/%s/' : '/%s', implode('/', $segments));
    }

    public function getSegments(): array
    {
        return $this->segments;
    }
}
