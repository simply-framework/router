<?php

namespace Simply\Router\Parser\Segment;

/**
 * ParsedSegment.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface SegmentInterface
{
    public function getNormalizedPath(): string;

    public function getNames(): array;

    public function getFormat(): string;

    public function isDynamic(): bool;
}
