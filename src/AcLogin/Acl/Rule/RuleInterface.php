<?php


interface AcLogin_Acl_Rule_RuleInterface
{


    /**
     * Returns true, if the rule is active.
     * 
     * @return boolean
     */
    public function isEnabled ();


    /**
     * Evaluates the rule and returns true or false.
     * 
     * @param AcLogin_RemoteUser $user
     * @param array $context
     * @return boolean
     */
    public function evaluate (AcLogin_RemoteUser $user, Array $context = array());


    /**
     * Returns the rule label.
     * 
     * @return string
     */
    public function getLabel ();


    /**
     * Returns the "deny" message - set when the rule has been evaluated to false.
     * 
     * @return string
     */
    public function getDenyMessage ();


    /**
     * Returns the string representation of the object.
     * 
     * @return string
     */
    public function __toString ();
}