<?php


/**
 * Handles the remote user info, gathered from an external AAI (Shibboleth)
 *
 */
class AcLogin_RemoteUser extends AcLogin_Base
{

    /**
     * The unique ID of the user determined for the AC server.
     *
     * @var string|NULL
     */
    protected $_uid = NULL;

    /**
     * The REMOTE_USER variable value.
     *
     * @var string|NULL
     */
    protected $_rawUid = NULL;

    /**
     * Values from the server environment ($_SERVER)
     * 
     * @var array
     */
    protected $_serverVars = array();

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
    public function __construct(Array $options, Array $serverVars = NULL)
    {
        $this->setOptions($options);
        
        if (NULL === $serverVars) {
            $serverVars = $_SERVER;
        }
        $this->_serverVars = $serverVars;
        
        $this->_attrs = new ArrayObject();
        $this->_initAttrMap();
        $this->_loadUserInfo();
    }


    protected function _initAttrMap()
    {
        foreach ($this->_attrMapConfig as $fieldConfig => $attrName) {
            $fieldName = $this->getOption($fieldConfig);
            if ($fieldName) {
                $this->_attrMap[$fieldName] = $attrName;
            }
        }
    }


    /**
     * Loads remote user data (from the server environment)
     */
    protected function _loadUserInfo()
    {
        $this->_uid = $this->_extractUid();
        if (! $this->_uid) {
            return;
        }
        
        foreach ($this->_attrMap as $fieldName => $attrName) {
            $attrValue = $this->getServerVar($fieldName);
            if (NULL !== $attrValue) {
                $this->setAttribute($attrName, $this->_parseAttrValue($attrValue));
            }
        }
    }


    /**
     * Determine the unique user ID for the AC server.
     *
     * @return string
     */
    protected function _extractUid()
    {
        // if the preferred 'uid_field' variable is set, return it without modification
        $uidField = $this->getOption('uid_field');
        if ($uidField && (NULL !== ($uidValue = $this->getServerVar($uidField)))) {
            return $uidValue;
        }
        
        // when using the universal REMOTE_USER variable, we should do some modifications
        // to ensure, that the format of the UID will be the same for all possible formats
        // (eppn, computed-id, stored-id, ...)
        $remoteUserField = $this->getOption('remote_user_field');
        $remoteUserFieldValue = $this->getServerVar($remoteUserField);
        if (NULL !== $remoteUserFieldValue) {
            $parts = parse_url($this->getServerVar('Shib-Identity-Provider'));
            
            if (isset($parts['host'])) {
                $this->_rawUid = $remoteUserFieldValue;
                $uid = sprintf("%s@%s", md5($remoteUserFieldValue), $parts['host']);
                return $uid;
            }
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
    protected function _parseAttrValue($value)
    {
        $parts = explode(';', $value);
        // FIXME - returns only the first attribute value
        return $parts[0];
    }


    /**
     * Returns if the user is valid (i.e. the user ID has been set)
     *
     * @return boolean
     */
    public function isValid()
    {
        if (NULL === $this->getUid()) {
            return false;
        }
        
        /*
        if (! $this->validateAttributes()) {
            return false;
        }
        */
        
        return true;
    }


    /**
     * Returns the determined UID.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_uid;
    }


    /**
     * Returns the 'raw' REMOTE_USER value.
     *
     * @return string
     */
    public function getRawUid()
    {
        return $this->_rawUid;
    }


    /**
     * Returns the user attributes.
     *
     * @return ArrayObject
     */
    public function getAttributes()
    {
        return $this->_attrs;
    }


    /**
     * Sets an attribute.
     *
     * @param string $attrName
     * @param string $attrValue
     */
    public function setAttribute($attrName, $attrValue)
    {
        $this->_attrs->offsetSet($attrName, $attrValue);
    }


    /**
     * Returns an attribute.
     *
     * @param string $attrName
     * @return string
     */
    public function getAttribute($attrName)
    {
        if ($this->_attrs->offsetExists($attrName)) {
            return $this->_attrs->offsetGet($attrName);
        }
        
        return NULL;
    }


    /**
     * Returns true, if the attribute is set.
     * 
     * @param string $attrName
     * @return boolean
     */
    public function isSetAttribute($attrName)
    {
        return (NULL !== $this->getAttribute($attrName));
    }


    /**
     * Validates the user attributes.
     * 
     * @throws AcLogin_Exception
     */
    public function validateAttributes()
    {
        $requiredAttrs = $this->getOption('required_attributes');
        $missingAttrs = array();
        
        if (NULL !== $requiredAttrs) {
            $attrNames = preg_split('/\s+/', $requiredAttrs);
            foreach ($attrNames as $attrName) {
                if (NULL === $this->getServerVar($attrName)) {
                    $missingAttrs[] = $attrName;
                }
            }
        }
        
        if (! empty($missingAttrs)) {
            throw new AcLogin_Exception(sprintf("Missing user attributes: %s", implode(', ', $missingAttrs)));
        }
    }


    /**
     * Returns a raw (environment) attribute.
     * 
     * @param string $attrName
     * @return string|NULL
     */
    public function getRawAttribute($varName)
    {
        return $this->getServerVar($varName);
    }


    /**
     * Returns an array of the 'required' attributes, the ones defined in the mapping property (_attrMap)
     *
     * @return array
     */
    public function getRequiredAttributes()
    {
        return array_keys($this->_attrMap);
    }


    /**
     * Returns user's email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getAttribute('email');
    }


    /**
     * Returns user's first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->getAttribute('first_name');
    }


    /**
     * Returns user's surname.
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->getAttribute('surname');
    }


    /**
     * Returns user's full name (first name and surname)
     *
     * @return string
     */
    public function getFullName()
    {
        return sprintf("%s %s", $this->getFirstName(), $this->getSurname());
    }


    /**
     * Returns a server variable value.
     * 
     * @param string $varName
     * @return string|NULL
     */
    public function getServerVar($varName)
    {
        if (isset($this->_serverVars[$varName])) {
            return $this->_serverVars[$varName];
        }
        
        return NULL;
    }
}