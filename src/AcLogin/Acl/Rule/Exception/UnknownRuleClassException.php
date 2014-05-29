<?php


/**
 * Thrown if the rule name cannot be resolved to the corresponding class name.
 */
class AcLogin_Acl_Rule_Exception_UnknownRuleClassException extends Exception
{


    public function __construct($ruleName)
    {
        parent::__construct(sprintf("Unknown rule '%s'", $ruleName));
    }
}