<?php


/**
 * Base object.
 *
 */
class AcLogin_Base
{

    /**
     * Option container.
     *
     * @var ArrayObject
     */
    protected $_options = NULL;


    /**
     * Sets options for the object.
     *
     * @param array $options
     */
    public function setOptions(Array $options = array())
    {
        $this->_options = new ArrayObject($options);
    }


    /**
     * Returns the value of an option.
     *
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName)
    {
        if (($this->_options instanceof ArrayObject) && $this->_options->offsetExists($optionName)) {
            return $this->_options->offsetGet($optionName);
        }
        
        return NULL;
    }
}
