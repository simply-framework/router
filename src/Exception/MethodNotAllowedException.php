<?php

namespace Simply\Router\Exception;

/**
 * Exception that is thrown when the path matches, but the HTTP request method does not match any known route.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class MethodNotAllowedException extends RoutingException
{
    /** @var string[] List of HTTP request methods that would be allowed */
    private $allowedMethods;

    /**
     * MethodNotAllowedException constructor.
     * @param string $message The exception message
     * @param string[] $allowedMethods List of HTTP request methods that are allowed
     */
    public function __construct(string $message, array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;

        parent::__construct($message);
    }

    /**
     * Returns a list of allowed HTTP request methods.
     * @return string[] List of allowed HTTP request methods
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
