<?php


/**
 * Thrown when the rule misses a required parameter.
 */
class AcLogin_Acl_Rule_Exception_MissingParamException extends Exception
{


    public function __construct($paramName)
    {
        parent::__construct(sprintf("Missing required parameter '%s'", $paramName));
    }
}