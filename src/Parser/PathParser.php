<?php

namespace Simply\Router\Parser;

use Simply\Router\Parser\Segment\DynamicSegment;
use Simply\Router\Parser\Segment\PlaceholderSegment;
use Simply\Router\Parser\Segment\SegmentInterface;
use Simply\Router\Parser\Segment\StaticSegment;

/**
 * RouteParser.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PathParser
{
    public function parse(string $path): ParsedPath
    {
        $names = [];
        $segments = [];

        foreach (preg_split('#/#', $path, -1, \PREG_SPLIT_NO_EMPTY) as $segment) {
            $parsed = $this->parseSegment($segment);
            $newNames = array_flip($parsed->getNames());
            $duplicates = array_intersect_key($newNames, $names);

            if ($duplicates) {
                throw new \InvalidArgumentException(
                    "Duplicate placeholder names in path '$path': " . implode(', ', $duplicates)
                );
            }

            $names += $newNames;
            $segments[] = $parsed;
        }

        $canonicalSlash = \count($segments) > 0 && substr($path, -1) === '/';

        return new ParsedPath($segments, $canonicalSlash);
    }

    public function parseSegment(string $segment): SegmentInterface
    {
        $placeholders = $this->parsePlaceholders($segment);

        if (empty($placeholders)) {
            return new StaticSegment($segment, $this->encodeFormatString($segment));
        }

        if (\count($placeholders) === 1) {
            $placeholder = reset($placeholders);

            if ($placeholder->hasSimplePattern() && $placeholder->getLength() === \strlen($segment)) {
                return new PlaceholderSegment($placeholder->getName());
            }
        }

        return $this->createDynamicSegment($segment, $placeholders);
    }

    /**
     * @param string $segment
     * @return Placeholder[]
     */
    private function parsePlaceholders(string $segment): array
    {
        $matches = [];
        $placeholders = [];

        preg_match_all(
            "/\{(?'name'[a-z0-9_]++)(?::(?'pattern'(?:[^{}]++|\{(?&pattern)\})++))?\}/i",
            $segment,
            $matches,
            \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL
        );

        foreach ($matches as $match) {
            $placeholders[] = new Placeholder(
                $match['name'][0],
                $match['pattern'][0] ?? null,
                $match[0][1],
                \strlen($match[0][0])
            );
        }

        return $placeholders;
    }

    /**
     * @param string $segment
     * @param Placeholder[] $placeholders
     * @return DynamicSegment
     */
    private function createDynamicSegment(string $segment, array $placeholders): DynamicSegment
    {
        $names = [];
        $placeholders = array_values($placeholders);
        $part = substr($segment, 0, $placeholders[0]->getOffset());
        $pattern = preg_quote($part, '/');
        $format = $this->encodeFormatString($part);

        foreach ($placeholders as $index => $placeholder) {
            $names[] = $placeholder->getName();
            $pattern .= sprintf("(?'%s'%s)", $placeholder->getName(), $placeholder->getPattern());
            $format .= '%s';

            $start = $placeholder->getOffset() + $placeholder->getLength();

            $part = isset($placeholders[$index + 1])
                ? substr($segment, $start, $placeholders[$index + 1]->getOffset() - $start)
                : substr($segment, $start);

            $pattern .= preg_quote($part, '/');
            $format .= $this->encodeFormatString($part);
        }

        return new DynamicSegment($names, "/^(?:$pattern)$/", $format);
    }

    private function encodeFormatString(string $part): string
    {
        return str_replace('%', '%%', rawurlencode($part));
    }
}
