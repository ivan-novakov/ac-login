<?php


/**
 * Abstract ACL rule class.
 *
 */
abstract class AcLogin_Acl_Rule_AbstractRule implements AcLogin_Acl_Rule_RuleInterface
{

    const TYPE_PERMIT = 'permit';

    const TYPE_DENY = 'deny';

    /**
     * Rule label.
     * 
     * @var string
     */
    protected $_label = '';

    /**
     * Rule params.
     * 
     * @var array
     */
    protected $_params = array();

    /**
     * List of required params.
     * 
     * @var array
     */
    protected $_requiredParams = array();


    /**
     * Constructor.
     * 
     * @param string $label
     * @param array $params
     */
    public function __construct($label, Array $params = array())
    {
        $this->_label = $label;
        $this->_params = $params;
    }


    /**
     * Returns the rule label.
     * 
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }


    /**
     * Returns the deny message.
     * 
     * @return string
     */
    public function getDenyMessage()
    {
        return $this->_getParam('denyMessage', 'action_denied');
    }


    /**
     * (non-PHPdoc) 
     * @see AcLogin_Acl_Rule_RuleInterface::isEnabled()
     */
    public function isEnabled()
    {
        if ($this->_getParam('enabled')) {
            return true;
        }
        
        return false;
    }


    /**
     * (non-PHPdoc)
     * @see AcLogin_Acl_Rule_RuleInterface::__toString()
     */
    public function __toString()
    {
        return $this->getLabel();
    }


    /**
     * Finalizes evaluation accordingly to the rule type - permit/deny.
     * 
     * @param boolean $evaluation
     * @return boolean
     */
    protected function _evaluateDenyPermit($evaluation)
    {
        switch ($this->_getType()) {
            case self::TYPE_PERMIT:
                if ($evaluation) {
                    return true;
                }
                break;
            
            case self::TYPE_DENY:
                if (! $evaluation) {
                    return true;
                }
                break;
            
            default:
                break;
        }
        
        return false;
    }


    /**
     * Returns the rule type - permit/deny.
     * 
     * @return string
     */
    protected function _getType()
    {
        return $this->_getParam('type', self::TYPE_DENY);
    }


    /**
     * Returns a specific parameter.
     * 
     * @param string $name
     * @param mixed $defaultValue
     * @throws AcLogin_Acl_Rule_Exception_MissingParamException
     * @return mixed
     */
    protected function _getParam($name, $defaultValue = NULL)
    {
        if (isset($this->_params[$name])) {
            return $this->_params[$name];
        }
        
        if ($this->_isRequiredParam($name)) {
            throw new AcLogin_Acl_Rule_Exception_MissingParamException($name);
        }
        
        return $defaultValue;
    }


    /**
     * Returns true, if the parameter is required.
     * 
     * @param string $name
     * @return boolean
     */
    protected function _isRequiredParam($name)
    {
        return (in_array($name, $this->_requiredParams));
    }
}