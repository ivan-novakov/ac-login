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
    public function setOptions (Array $options = array())
    {
        $this->_options = new ArrayObject($options);
    }


    /**
     * Returns the value of an option.
     *
     * @param string $optionName
     * @return mixed
     */
    public function getOption ($optionName)
    {
        if (($this->_options instanceof ArrayObject) && $this->_options->offsetExists($optionName)) {
            return $this->_options->offsetGet($optionName);
        }
        
        return NULL;
    }

}
