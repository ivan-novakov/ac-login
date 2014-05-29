<?php


/**
 * The ACL object, making the ACL decisions based on rules.
 *
 */
class AcLogin_Acl
{

    /**
     * Configuration object.
     * 
     * @var Zend_Config
     */
    protected $_config = NULL;

    /**
     * Default rule class prefix.
     * 
     * @var string
     */
    protected $_defaultClassPrefix = 'AcLogin_Acl_Rule_';

    /**
     * The last failed rule.
     * 
     * @var AcLogin_Acl_Rule_RuleInterface
     */
    protected $_failedRule = NULL;


    /**
     * Constructor.
     * 
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->_config = $config;
    }


    /**
     * Returns true, if the user is allowed to log in.
     * 
     * @param AcLogin_RemoteUser $user
     * @param array $context
     * @return boolean
     */
    public function isAllowed(AcLogin_RemoteUser $user, Array $context = array())
    {
        if (! $this->isAclEnabled()) {
            return true;
        }
        
        $rules = $this->_initRules($this->_config->rules->toArray());
        
        foreach ($rules as $ruleLabel => $rule) {
            if ($rule->isEnabled() && ! $rule->evaluate($user, $context)) {
                $this->_failedRule = $rule;
                return false;
            }
        }
        
        return true;
    }


    /**
     * Returns the last failed rule.
     * 
     * @return AcLogin_Acl_Rule_RuleInterface
     */
    public function getFailedRule()
    {
        return $this->_failedRule;
    }


    /**
     * Returns true, if the ACL is active.
     * 
     * @return boolean
     */
    public function isAclEnabled()
    {
        if ($this->_config->options->enabled) {
            return true;
        }
        
        return false;
    }


    /**
     * Initializes the ACL rule objects.
     * 
     * @param array $rulesConfig
     * @return array
     */
    protected function _initRules(Array $rulesConfig)
    {
        $rules = array();
        foreach ($rulesConfig as $ruleLabel => $ruleData) {
            $rules[$ruleLabel] = $this->_initRule($ruleLabel, $ruleData['rule'], $ruleData['params']);
        }
        
        return $rules;
    }


    /**
     * Initializes an ACL rule object.
     * 
     * @param string $label
     * @param string $name
     * @param array $params
     * @throws AcLogin_Acl_Rule_Exception_UnknownRuleClassException
     * @return AcLogin_Acl_Rule_RuleInterface
     */
    protected function _initRule($label, $name, Array $params = array())
    {
        $className = $this->_getRuleClassName($name);
        if (! class_exists($className)) {
            throw new AcLogin_Acl_Rule_Exception_UnknownRuleClassException($name);
        }
        
        return new $className($label, $params);
    }


    /**
     * Resolves the ACL rule object class name.
     * 
     * @param string $name
     * @return string
     */
    protected function _getRuleClassName($name)
    {
        $classPrefix = $this->_defaultClassPrefix;
        if ($this->_config->options->ruleClassPrefix) {
            $classPrefix = $this->_config->options->ruleClassPrefix;
        }
        
        return $classPrefix . $name;
    }
}