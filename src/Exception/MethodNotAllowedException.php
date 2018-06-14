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

    public function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;

        parent::__construct("The requested method is not within list of allowed methods");
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}