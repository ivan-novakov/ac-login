<?php


/**
 * Simple ACL rule, which evaluates a single condition - if an attribute matches a value.
 *
 */
class AcLogin_Acl_Rule_UserAttrExprRule extends AcLogin_Acl_Rule_AbstractRule
{

    const OP_EQUAL = '==';

    const OP_NOT_EQUAL = '!=';

    /**
     * The required parameters.
     * 
     * @var array
     */
    protected $_requiredParams = array(
        'attributeName',
        'attributeMatchValue'
    );


    /**
       * (non-PHPdoc)
       * @see AcLogin_Acl_Rule_RuleInterface::evaluate()
       */
    public function evaluate(AcLogin_RemoteUser $user, Array $context = array())
    {
        $attributeName = $this->_getAttributeName();
        $matchValue = $this->_getMatchValue();
        $operator = $this->_getOperator();
        
        $userAttributeValue = $user->getRawAttribute($attributeName);
        
        if (NULL === $userAttributeValue && $this->_getParam('ignoreMissingAttribute')) {
            return true;
        }
        
        $match = ($user->getRawAttribute($attributeName) == $matchValue);
        
        if (self::OP_EQUAL != $operator) {
            $match = ! $match;
        }
        
        return $this->_evaluateDenyPermit($match);
    }


    public function __toString()
    {
        return sprintf("%s: %s [%s %s %s]", get_class($this), $this->getLabel(), $this->_getAttributeName(), $this->_getOperator(), $this->_getMatchValue());
    }


    /**
     * Returns the attribute name to match.
     * 
     * @return string
     */
    protected function _getAttributeName()
    {
        return $this->_getParam('attributeName');
    }


    /**
     * Returns the match value.
     * 
     * @return string
     */
    protected function _getMatchValue()
    {
        return $this->_getParam('attributeMatchValue');
    }


    /**
     * Returns the operator.
     * 
     * @return string
     */
    protected function _getOperator()
    {
        return $this->_getParam('operator', self::OP_EQUAL);
    }
}