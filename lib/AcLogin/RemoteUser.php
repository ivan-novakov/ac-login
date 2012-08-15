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
 * Handles the remote user info, gathered from an external AAI (Shibboleth)
 *
 */
class AcLogin_RemoteUser extends AcLogin_Base
{

    /**
     * The unique ID of the user determined for the AC server.
     *
     * @var string
     */
    protected $_uid = '';

    /**
     * The REMOTE_USER variable value.
     *
     * @var string
     */
    protected $_rawUid = '';

    /**
     * User attributes container.
     *
     * @var ArrayObject
     */
    protected $_attrs = NULL;

    /**
     * Maps external attributes to local ones.
     *
     * @var array
     */
    protected $_attrMapConfig = array(
        'uid_field' => 'uid', 
        'givenName_field' => 'first_name', 
        'sn_field' => 'surname', 
        'mail_field' => 'email'
    );

    protected $_attrMap;


    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct (Array $options)
    {
        $this->setOptions($options);
        $this->_attrs = new ArrayObject();
        $this->_initAttrMap();
        $this->_loadUserInfo();
    }


    protected function _initAttrMap ()
    {
        foreach ($this->_attrMapConfig as $fieldConfig => $attrName) {
            $fieldName = $this->getOption($fieldConfig);
            if ($fieldName) {
                $this->_attrMap[$fieldName] = $attrName;
            }
        }
    }


    /**
     * Loads remote user data (from the $_SERVER environment)
     *
     */
    protected function _loadUserInfo ()
    {
        $this->_uid = $this->_extractUid();
        if (! $this->_uid) {
            return;
        }
        
        foreach ($this->_attrMap as $fieldName => $attrName) {
            if (isset($_SERVER[$fieldName])) {
                $this->setAttribute($attrName, $this->_parseAttrValue($_SERVER[$fieldName]));
            }
        }
    }


    /**
     * Determine the unique user ID for the AC server.
     *
     * @return string
     */
    protected function _extractUid ()
    {
        // if the preferred 'uid_field' variable is set, return it without modification
        $uidField = $this->getOption('uid_field');
        if ($uidField && isset($_SERVER[$uidField])) {
            return $_SERVER[$uidField];
        }
        
        // when using the universal REMOTE_USER variable, we should do some modifications
        // to ensure, that the format of the UID will be the same for all possible formats
        // (eppn, computed-id, stored-id, ...)
        $remoteUserField = $this->getOption('remote_user_field');
        if (isset($_SERVER[$remoteUserField])) {
            $parts = parse_url($_SERVER['Shib-Identity-Provider']);
            
            $this->_rawUid = $_SERVER[$remoteUserField];
            $uid = sprintf("%s@%s", md5($_SERVER[$remoteUserField]), $parts['host']);
            return $uid;
        }
        
        return NULL;
    }


    /**
     * Parse attribute value - handle mutivalue attributes.
     * Currently it returns only the first attribute.
     *
     * @param string $value
     * @return string
     */
    protected function _parseAttrValue ($value)
    {
        $parts = explode(';', $value);
        // FIXME - returns only the first attribute value
        return $parts[0];
    }


    /**
     * Returns if the user is valid (i.e. the user ID has been set)
     *
     * @return integer
     */
    public function isValid ()
    {
        return $this->getUid();
    }


    /**
     * Returns the determined UID.
     *
     * @return string
     */
    public function getUid ()
    {
        return $this->_uid;
    }


    /**
     * Returns the 'raw' REMOTE_USER value.
     *
     * @return string
     */
    public function getRawUid ()
    {
        return $this->_rawUid;
    }


    /**
     * Returns the user attributes.
     *
     * @return ArrayObject
     */
    public function getAttributes ()
    {
        return $this->_attrs;
    }


    /**
     * Sets an attribute.
     *
     * @param string $attrName
     * @param string $attrValue
     */
    public function setAttribute ($attrName, $attrValue)
    {
        $this->_attrs->offsetSet($attrName, $attrValue);
    }


    /**
     * Returns an attribute.
     *
     * @param string $attrName
     * @return string
     */
    public function getAttribute ($attrName)
    {
        if ($this->_attrs->offsetExists($attrName)) {
            return $this->_attrs->offsetGet($attrName);
        }
        
        return NULL;
    }


    /**
     * Returns a raw (environment) attribute.
     * 
     * @param string $attrName
     * @return string|NULL
     */
    public function getRawAttribute ($attrName)
    {
        if (isset($_SERVER[$attrName])) {
            return $_SERVER[$attrName];
        }
        
        return NULL;
    }


    /**
     * Returns an array of the 'required' attributes, the ones defined in the mapping property (_attrMap)
     *
     * @return array
     */
    public function getRequiredAttributes ()
    {
        return array_keys($this->_attrMap);
    }


    /**
     * Returns user's email.
     *
     * @return string
     */
    public function getEmail ()
    {
        return $this->getAttribute('email');
    }


    /**
     * Returns user's first name.
     *
     * @return string
     */
    public function getFirstName ()
    {
        return $this->getAttribute('first_name');
    }


    /**
     * Returns user's surname.
     *
     * @return string
     */
    public function getSurname ()
    {
        return $this->getAttribute('surname');
    }


    /**
     * Returns user's full name (first name and surname)
     *
     * @return string
     */
    public function getFullName ()
    {
        return sprintf("%s %s", $this->getFirstName(), $this->getSurname());
    }
}