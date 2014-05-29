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

// require_once '../init.php';
require __DIR__ . '/../vendor/autoload.php';

// The application root directory
define('ACLOGIN_DIR', __DIR__ . '/../'); 

// The directory with containing the configuration files
define('ACLOGIN_CONFIG_DIR', ACLOGIN_DIR . 'config' . DIRECTORY_SEPARATOR);

// The template directory
define('ACLOGIN_TPL_DIR', ACLOGIN_DIR . 'tpl' . DIRECTORY_SEPARATOR);

$app = new AcLogin_Application(array(
    'config_file' => ACLOGIN_CONFIG_DIR . 'aclogin.ini'
));

$app->main();
