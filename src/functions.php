<?php

namespace Simply\Router;

/**
 * Splits the given path into nonempty segments.
 * @param string $path The path to split
 * @return string[] The non empty segments from the path
 */
function split_segments(string $path): array
{
    return string_split('#/#', $path, -1, \PREG_SPLIT_NO_EMPTY);
}

/**
 * Splits the string into parts using regular expressions.
 * @param string $pattern The pattern to use for splitting
 * @param string $subject The string to split
 * @param int $limit Maximum number of parts or -1 for unlimited
 * @param int $flags The flags for the preg_split
 * @return string[] Split parts from the string
 */
function string_split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array
{
    $parts = preg_split($pattern, $subject, $limit, $flags);

    if (!\is_array($parts) || preg_last_error() !== \PREG_NO_ERROR) {
        throw new \RuntimeException('Error splitting string');
    }

    return $parts;
}
