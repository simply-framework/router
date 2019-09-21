<?php

namespace Simply\Router\Parser;

use Simply\Router\RegularExpressionValidator;

/**
 * Placeholder.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Placeholder
{
    private $name;
    private $pattern;
    private $offset;
    private $length;

    public function __construct(string $name, ?string $pattern, int $offset, int $length)
    {
        $this->name = $name;
        $this->pattern = $pattern ?? '.+';
        $this->offset = $offset;
        $this->length = $length;

        $error = RegularExpressionValidator::getError("/$pattern/");

        if ($error !== null) {
            throw new \InvalidArgumentException(sprintf("Invalid regular expression '%s': %s", $pattern, $error));
        }
    }

    private function validatePattern(string $pattern): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, \E_ALL);

        $error = null;

        try {
            $result = preg_match("/$pattern/", '');

            if ($result === false || preg_last_error() !== \PREG_NO_ERROR) {
                $error = current(preg_grep('/^PREG_/', array_keys(get_defined_constants(), preg_last_error(), true)));
            }
        } catch (\ErrorException $exception) {
            $error = $exception->getMessage();
        } finally {
            restore_error_handler();
        }

        if ($error !== null) {
            throw new \InvalidArgumentException(sprintf("Error parsing regular expression '%s': %s", $pattern, $error));
        }
    }

    public function hasSimplePattern(): bool
    {
        return $this->pattern === '.+';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLength(): int
    {
        return $this->length;
    }
}
