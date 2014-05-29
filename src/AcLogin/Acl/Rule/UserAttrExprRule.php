<?php


/**
 * This file is part of the AC Login Service.
 *
 * The AC Login Service is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The AC Login Service is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with the AC Login Service.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ivan Novakov <ivan.novakov@cesnet.cz>
 * @copyright Copyright (c) 2009-2012 CESNET, z. s. p. o. (http://www.ces.net/)
 * @license LGPL (http://www.gnu.org/licenses/lgpl.txt)
 *
 */

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
    public function evaluate (AcLogin_RemoteUser $user, Array $context = array())
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


    public function __toString ()
    {
        return sprintf("%s: %s [%s %s %s]", get_class($this), $this->getLabel(), $this->_getAttributeName(), $this->_getOperator(), $this->_getMatchValue());
    }


    /**
     * Returns the attribute name to match.
     * 
     * @return string
     */
    protected function _getAttributeName ()
    {
        return $this->_getParam('attributeName');
    }


    /**
     * Returns the match value.
     * 
     * @return string
     */
    protected function _getMatchValue ()
    {
        return $this->_getParam('attributeMatchValue');
    }


    /**
     * Returns the operator.
     * 
     * @return string
     */
    protected function _getOperator ()
    {
        return $this->_getParam('operator', self::OP_EQUAL);
    }
}