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
    public function __construct ($label, Array $params = array())
    {
        $this->_label = $label;
        $this->_params = $params;
    }


    /**
     * Returns the rule label.
     * 
     * @return string
     */
    public function getLabel ()
    {
        return $this->_label;
    }


    /**
     * Returns the deny message.
     * 
     * @return string
     */
    public function getDenyMessage ()
    {
        return $this->_getParam('denyMessage', 'action_denied');
    }


    /**
     * Finalizes evaluation accordingly to the rule type - permit/deny.
     * 
     * @param boolean $evaluation
     * @return boolean
     */
    protected function _evaluateDenyPermit ($evaluation)
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
    protected function _getType ()
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
    protected function _getParam ($name, $defaultValue = NULL)
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
    protected function _isRequiredParam ($name)
    {
        return (in_array($name, $this->_requiredParams));
    }
}