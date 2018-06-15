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
    private $allowedMethods;

    public function __construct($message, array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;

        parent::__construct($message);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}