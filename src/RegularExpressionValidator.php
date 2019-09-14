<?php

namespace Simply\Router;

/**
 * RegularExpressionValidator.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RegularExpressionValidator
{
    public static function isValid(string $pattern): bool
    {
        return self::getError($pattern) === null;
    }

    public static function getError(string $pattern): ?string
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, \E_ALL);

        try {
            $result = preg_match($pattern, '');

            if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
                return current(preg_grep('/^PREG_/', array_keys(get_defined_constants(), preg_last_error())));
            }
        } catch (\Throwable $exception) {
            return preg_replace('/^.*?preg_match\(\): /', '', $exception->getMessage());
        } finally {
            restore_error_handler();
        }

        return null;
    }
}
