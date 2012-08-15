<?php


interface AcLogin_Acl_Rule_RuleInterface
{


    public function evaluate (AcLogin_RemoteUser $user, Array $context = array());


    public function getLabel ();


    public function getDenyMessage ();
}