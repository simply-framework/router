<?php

namespace Simply\Router\Parser;

/**
 * OptionalParser.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class OptionalParser
{
    private $path;

    public function parseOptionalPaths(string $path): array
    {
        if (strpos($path, '[') === false && strpos($path, ']') === false) {
            return [$path];
        }

        $this->path = $path;

        return $this->deduplicate($this->parseForks(true));
    }

    private function parseForks(bool $top): array
    {
        $paths = [''];

        do {
            [$string, $token] = $this->consumeToken();

            if ($string !== '') {
                $paths = array_map(static function (string $path) use ($string): string {
                    return $path . $string;
                }, $paths);
            }

            if ($token === '[') {
                $newPaths = array_filter($this->parseForks(false), '\strlen');

                foreach ($paths as $old) {
                    foreach ($newPaths as $new) {
                        $paths[] = $old . $new;
                    }
                }
            } elseif ($token === ']') {
                if ($top) {
                    throw new \InvalidArgumentException('Uneven number of [ and ] characters in the path');
                }

                return $paths;
            }
        } while ($token !== '');

        if (!$top) {
            throw new \InvalidArgumentException('Uneven number of [ and ] characters in the path');
        }

        return $paths;
    }

    private function consumeToken(): array
    {
        $string = '';

        do {
            preg_match('/{[a-z0-9_]++(?::((?:[^{}]++|{(?1)})++))?}|\[|]|$/i', $this->path, $match, PREG_OFFSET_CAPTURE);
            $length = strlen($match[0][0]);
            $string .= substr($this->path, 0, $match[0][1] + ($length > 1 ? $length : 0));
            $this->path = substr($this->path, $match[0][1] + $length);
        } while ($length > 1);

        return [$string, $match[0][0]];
    }

    private function deduplicate(array $paths): array
    {
        $result = [];
        $list = [];

        foreach ($paths as $path) {
            $normalised = implode('/', preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY));

            if (!isset($list[$normalised])) {
                $list[$normalised] = true;
                $result[] = $path;
            }
        }

        return $result;
    }
}
