<?php
/**
 * Created by PhpStorm.
 * User: riimu
 * Date: 14/06/2018
 * Time: 15.39
 */

namespace Simply\Router\Exception;


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

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}