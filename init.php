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
 * @author Ivan Novakov <ivan.novakov@debug.cz>
 * @copyright Copyright (c) 2009-2011 CESNET, z. s. p. o. (http://www.ces.net/)
 * @license LGPL (http://www.gnu.org/licenses/lgpl.txt)
 * 
 */

// The application root directory
define('ACLOGIN_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// The library directory 
define('ACLOGIN_LIB_DIR', ACLOGIN_DIR . 'lib' . DIRECTORY_SEPARATOR);

// The WWW document root
define('ACLOGIN_WWW_DIR', ACLOGIN_DIR . 'www' . DIRECTORY_SEPARATOR);

// The directory with containing the configuration files
define('ACLOGIN_CONFIG_DIR', ACLOGIN_DIR . 'config' . DIRECTORY_SEPARATOR);

// The template directory
define('ACLOGIN_TPL_DIR', ACLOGIN_DIR . 'tpl' . DIRECTORY_SEPARATOR);

// The Zend framework directory
define('ZEND_FW_DIR', '/var/lib/php/zend/');

// The directory, where the AC PHP API is installed
define('ACAPI_LIB_DIR', '/var/lib/php/devel/acapi/');

set_include_path(ACLOGIN_LIB_DIR . PATH_SEPARATOR . ZEND_FW_DIR . PATH_SEPARATOR . ACAPI_LIB_DIR . PATH_SEPARATOR . get_include_path());

// Initialization of the Zend Loader
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('AcLogin_');
$autoloader->registerNamespace('AcApi_');

//-----------------
function _log ($value)
{
    error_log(print_r($value, true));
}