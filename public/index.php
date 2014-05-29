<?php
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
