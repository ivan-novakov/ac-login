<?php


/**
 * The application class, containing the main() method and the base actions.
 */
class AcLogin_Application extends AcLogin_Base
{

    /**
     * Configuration object.
     * 
     * @var Zend_Config
     */
    protected $_config = NULL;

    /**
     * Logger object.
     * 
     * @var Zend_Log
     */
    protected $_log = NULL;

    /**
     * AC API client object.
     * 
     * @var AcApi_Client
     */
    protected $_client = NULL;

    /**
     * THe ACL object.
     * 
     * @var AcLogin_Acl
     */
    protected $_acl = NULL;

    /**
     * A custom password salt to use when generating user passwords, if one time passwords are enabled.
     * 
     * @var string
     */
    protected $_passwordSalt = NULL;

    protected $_configFileName = 'aclogin.ini';


    /**
     * Constructor.
     * 
     * Takes these options:
     * - 'config_file' - configuration file path
     *
     * @param array $options
     */
    public function __construct(Array $options)
    {
        $this->setOptions($options);
    }


    /**
     * Debugging shorthand.
     *
     * @param mixed $value
     */
    private function _debug($value)
    {
        error_log(print_r($value, true));
    }


    /**
     * The "main()" method, which si run at the highest level.
     *
     */
    public function main()
    {
        try {
            $this->_run();
        } catch (Exception $e) {
            // Panic - catch everything
            if ($this->_log instanceof Zend_Log) {
                $this->_log->crit(sprintf("main() %s: %s", get_class($e), $e->getMessage()));
            } else {
                $this->_debug("$e");
            }
        }
    }


    /**
     * Main routine.
     *
     */
    protected function _run()
    {
        // initialize config object
        $this->_initConfig();
        
        // initialize logging
        $this->_initLog();
        $this->_log->debug('Log started...');
        
        // Initialize remote user object based on received remote user info (Shibboleth)
        $serverVars = $_SERVER;
        $remoteUser = new AcLogin_RemoteUser($this->_config->shibboleth->toArray(), $serverVars);
        if (! $remoteUser->isValid()) {
            $this->_actionInvalidUser($remoteUser);
        }
        
        $uid = $remoteUser->getUid();
        
        $this->_log->notice(sprintf("Remote user: '%s' (%s), IdP: %s", $uid, $remoteUser->getRawUid(), $_SERVER['Shib-Identity-Provider']));
        
        // PEP
        $acl = $this->_getAcl();
        if (! $acl->isAllowed($remoteUser)) {
            $rule = $acl->getFailedRule();
            if (! $rule) {
                $this->_log->err('ACL failed, but no failed rule available');
                $this->_actionGeneralError('access denied');
            }
            
            $this->_log->err(sprintf("ACL rule failed: %s", $rule));
            $this->_actionGeneralError(sprintf($rule->getDenyMessage()));
        }
        
        // Initialize AC API client object
        $this->_initClient();
        
        // Login to AC server as adminitrator (proxy account)
        $resp = $this->_client->login();
        if ($resp->isError()) {
            $this->_actionAcError('login', $resp);
        }
        
        // Check if the user exists on the AC server
        $principalId = $this->_principalExists($remoteUser);
        if (! $principalId) {
            
            // If it is not allowed to create accounts on the fly, throw an error
            if (! $this->_config->account->create_user_if_not_exists) {
                $this->_actionAcError('non existent user', $resp);
            }
            
            // Create the user on the AC server
            $principalId = $this->_principalCreate($remoteUser);
            
            // Refresh user data at the Adobe Connect server
        } elseif ($this->_config->account->refresh_user_data_on_login) {
            $this->_log->info(sprintf("[%s] User data refresh on login enabled, updating user data", $uid));
            $this->_principalUpdate($remoteUser, $principalId);
        }
        
        // If the use of one time passwords is enabled
        if ($this->_config->account->use_ot_password) {
            $this->_log->notice(sprintf("[%s] OTP enabled, generating custom password salt", $uid));
            // Set a custom password salt - it is used in the _getPrincipalPassword() method
            $this->_setPasswordSalt(uniqid());
            // Update the user's password on the AC server
            $this->_principalUpdatePassword($remoteUser, $principalId);
        }
        
        // Logout as administrator (proxy account)
        $resp = $this->_client->logout();
        
        // Try to login as the remote user
        $resp = $this->_client->api_login(array(
            'login' => $uid,
            'password' => $this->_getPrincipalPassword($remoteUser)
        ));
        
        if ($resp->isError()) {
            // user login error
            $this->_actionAcError('user login', $resp);
        }
        
        $this->_log->notice(sprintf("[%s] User logged in to Adobe Connect server", $uid));
        
        // Retrieve the session string from the response
        $sessionString = $resp->getSessionString();
        if (! $sessionString) {
            // no session found error
            $this->_actionGeneralError('No session found');
        }
        
        if ($this->_isDebugMode()) {
            $this->_actionDebugPage(array(
                'user' => $remoteUser,
                'session' => $sessionString
            ));
            return;
        }
        
        // Pass the session string and redirect him to the appropriate URL
        $this->_log->info(sprintf("[%s] Redirecting user...", $uid));
        $this->_redirectUser($sessionString);
    }
    
    /*
     * Adobe Connect routines
     */
    
    /**
     * Checks if the user exists on the AC server.
     * 
     * Returns the principal-id field or 0 if not found.
     *
     * @param AcLogin_RemoteUser $remoteUser
     * @return integer
     */
    protected function _principalExists(AcLogin_RemoteUser $remoteUser)
    {
        $uid = $remoteUser->getUid();
        
        $resp = $this->_client->api_principalList(array(
            'filter-type' => 'user',
            'filter-login' => $remoteUser->getUid()
        ));
        
        if ($resp->isError()) {
            $this->_actionAcError('check user', $resp);
        }
        
        $data = $resp->getRawData();
        
        if (! $data->{'principal-list'}->principal['principal-id']) {
            $this->_log->notice(sprintf("[%s]] User account does not exist on the server", $uid));
            return 0;
        }
        
        $this->_log->info(sprintf("[%s] User account exists on the server", $remoteUser->getUid()));
        
        return (int) $data->{'principal-list'}->principal['principal-id'];
    }


    /**
     * Creates a new user on the AC server.
     * 
     * Returns the principal-id field of the created user.
     *
     * @param AcLogin_RemoteUser $remoteUser
     * @return integer
     */
    protected function _principalCreate(AcLogin_RemoteUser $remoteUser)
    {
        $uid = $remoteUser->getUid();
        $this->_log->info(sprintf("[%s] Creating new account", $uid));
        
        $firstName = $remoteUser->getFirstName();
        $surname = $remoteUser->getSurname();
        $email = $remoteUser->getEmail();
        $password = $this->_getPrincipalPassword($remoteUser);
        
        if (! $uid) {
            $this->_actionGeneralError('No user ID found in request');
        }
        
        try {
            $remoteUser->validateAttributes();
        } catch (Exception $e) {
            $this->_actionGeneralError(sprintf("[%s] Unable to create user: %s", $uid, $e->getMessage()));
        }
        
        /*
        if (! $uid || ! $firstName || ! $surname || ! $email) {
            $this->_actionGeneralError(sprintf("[%s] Insufficient data for user creation. Required attributes - %s (eppn='%s', givenName='%s', sn='%s', mail='%s')<br />" . "Ask your IdP administrator to allow these attributes for release to SP with entityID='%s'.", $uid, implode(', ', $remoteUser->getRequiredAttributes()), $uid, $firstName, $surname, $email, $this->_config->general->get('entity_id')));
        }
        */
        
        $resp = $this->_client->api_principalUpdate(array(
            'login' => $uid,
            'first-name' => $firstName,
            'last-name' => $surname,
            'email' => $email,
            'has-children' => 0,
            'type' => 'user',
            'send-email' => 0,
            'password' => $password
        ));
        
        if ($resp->isError()) {
            $this->_actionAcError('principal-update', $resp);
        }
        
        $xml = $resp->getRawData();
        $principalId = (string) $xml->principal['principal-id'];
        
        if (! $principalId) {
            $this->_actionGeneralError("General error creating user");
        }
        $this->_log->notice(sprintf("[%s] Created account [principal-id = %d] on the server", $uid, $principalId));
        
        // If a default group is set in the configuration, we'll add the user to it
        if ($defaultGroup = $this->_config->account->default_group) {
            // First, we should get the ID of the group
            $resp = $this->_client->api_principalList(array(
                'filter-type' => 'group',
                'filter-name' => $defaultGroup
            ));
            if ($resp->isError()) {
                $this->_actionAcError('principal-list', $resp);
            }
            
            $xml = $resp->getRawData();
            $groupId = (string) $xml->{'principal-list'}->principal['principal-id'];
            // If the group exists on the server, update the user membership
            if ($groupId) {
                $resp = $this->_client->api_groupMembershipUpdate(array(
                    'group-id' => $groupId,
                    'principal-id' => $principalId,
                    'is-member' => true
                ));
                if ($resp->isError()) {
                    $this->_actionAcError('group-membership-update', $resp);
                }
                
                $this->_log->notice(sprintf("[%s] User [%d] added to group '%s' [%d]", $uid, $principalId, $defaultGroup, $groupId));
            } else {
                $this->_log->warn(sprintf("[%s] Default group '%s' not found on the server", $uid, $defaultGroup));
            }
        }
        
        return $principalId;
    }


    /**
     * Updates some user data for an existing user on the server.
     * 
     * @param AcLogin_RemoteUser $remoteUser
     * @param integer $principalId
     */
    protected function _principalUpdate(AcLogin_RemoteUser $remoteUser, $principalId)
    {
        $uid = $remoteUser->getUid();
        
        $this->_log->info(sprintf("[%s] Updating data", $uid));
        
        $firstName = $remoteUser->getFirstName();
        $surname = $remoteUser->getSurname();
        $email = $remoteUser->getEmail();
        
        $updateValues = array();
        if (NULL !== $firstName) {
            $updateValues['first-name'] = $firstName;
        }
        
        if (NULL !== $surname) {
            $updateValues['last-name'] = $surname;
        }
        
        if (NULL !== $email) {
            $updateValues['email'] = $email;
        }
        
        if (empty($updateValues)) {
            $this->_log->notice(sprintf("[%s] No values to update", $uid));
            return;
        }
        
        $updateValues['principal-id'] = $principalId;
        
        $this->_log->debug(sprintf("[%s] Updating data: %s", $uid, http_build_query($updateValues)));
        
        $resp = $this->_client->api_principalUpdate($updateValues);
        if ($resp->isError()) {
            $this->_actionAcError('principal-update', $resp);
        }
        
        $this->_log->notice(sprintf("[%s] User data updated on the server", $uid));
    }


    /**
     * Updates the user's password using the 'user-update-pwd' API call.
     * 
     * @param AcLogin_RemoteUser $remoteUser
     * @param integer $principalId
     * @param string $newPassword
     */
    protected function _principalUpdatePassword(AcLogin_RemoteUser $remoteUser, $principalId, $newPassword = NULL)
    {
        $uid = $remoteUser->getUid();
        
        $this->_log->info(sprintf("[%s] Updating user password", $uid));
        
        if (NULL === $newPassword) {
            $newPassword = $this->_getPrincipalPassword($remoteUser);
        }
        
        $resp = $this->_client->api_userUpdatePwd(array(
            'user-id' => $principalId,
            'password' => $newPassword,
            'password-verify' => $newPassword
        ));
        
        if ($resp->isError()) {
            $this->_actionAcError('principal-update', $resp);
        }
        
        $this->_log->notice(sprintf("[%s] User password updated", $uid));
    }


    /**
     * Redirects the user to the AC server attaching the active session string.
     *
     * @param string $sessionString
     */
    protected function _redirectUser($sessionString)
    {
        if (isset($_GET['target']) && parse_url($_GET['target'])) {
            $redirectUri = $_GET['target'];
        } else {
            $redirectUri = $this->_config->account->get('redirect_uri');
        }
        
        $this->_log->debug(sprintf("Redirecting user to location: %s", $redirectUri));
        
        $uri = Zend_Uri::factory($redirectUri);
        $uri->setQuery(array(
            'session' => $sessionString
        ));
        
        header('Location: ' . $uri);
        exit();
    }
    
    /*
     * Initializations
     *
     */
    
    /**
     * Initializes the config object.
     *
     */
    protected function _initConfig()
    {
        $configFile = $this->_getConfigFilePath();
        if (! $configFile) {
            throw new AcLogin_Exception('No config file specified');
        }
        
        $this->_config = new Zend_Config_Ini($configFile);
    }


    /**
     * Initializes the log object
     *
     */
    protected function _initLog()
    {
        $logConfig = $this->_config->log;
        if (! $logConfig->file) {
            throw new AcLogin_Exception('Login file not specified');
        }
        
        $logFilePath = $logConfig->file;
        if (! preg_match('/^\//', $logFilePath)) {
            $logFilePath = ACLOGIN_DIR . $logFilePath;
        }
        $writer = new Zend_Log_Writer_Stream($logFilePath);
        $this->_log = new Zend_Log($writer);
        
        if (isset($logConfig->verbosity)) {
            $filter = new Zend_Log_Filter_Priority(intval($logConfig->verbosity));
            $this->_log->addFilter($filter);
        }
    }


    /**
     * Initializes the AC API client object.
     *
     */
    protected function _initClient()
    {
        $acConfig = $this->_config->acapi->toArray();
        $this->_client = new AcApi_Client($acConfig);
        $this->_log->info(sprintf("Initializing Adobe Connect Client, remote server: %s", $acConfig['uri']));
    }


    /**
     * Initializes the page output layout.
     *
     * @return Zend_Layout
     */
    protected function _initLayout()
    {
        $layout = new Zend_Layout();
        $layout->setLayoutPath($this->_getLayoutPath());
        
        return $layout;
    }


    /**
     * Returns the ACL object.
     * 
     * @return AcLogin_Acl
     */
    protected function _getAcl()
    {
        $aclFile = ACLOGIN_CONFIG_DIR . $this->_config->acl->acl_definition_file;
        if (! file_exists($aclFile) || ! is_file($aclFile) || ! is_readable($aclFile)) {
            throw new AcLogin_Exception(sprintf("ACL definitions file '%s' not found or invalid", $aclFile));
        }
        
        if (! ($this->_acl instanceof AcLogin_Acl)) {
            $this->_acl = new AcLogin_Acl(new Zend_Config(require $aclFile));
        }
        
        return $this->_acl;
    }
    
    /*
     * Miscelaneous actions/error handling.
     *
     */
    protected function _actionDebugPage(Array $params = array())
    {
        $this->_log->info(print_r($_SERVER, true));
        $this->_actionGeneralError('Debug mode: ' . print_r($params, true));
    }


    /**
     * Shows error 'invalid user'.
     *
     * @param AcLogin_RemoteUser $remoteUser
     */
    protected function _actionInvalidUser(AcLogin_RemoteUser $remoteUser)
    {
        $this->_actionGeneralError('Invalid remote user (no uid set)');
    }


    /**
     * Shows an AC error.
     *
     * @param string $action The remote method name which returned an error.
     * @param AcApi_Response $resp
     */
    protected function _actionAcError($action, AcApi_Response $resp)
    {
        $error = $resp->getError();
        $errorMessage = sprintf("%s :: %s", $action, $error->getMessage());
        $this->_actionGeneralError($errorMessage);
    }


    /**
     * Shows a general error.
     *
     * @param unknown_type $message
     */
    protected function _actionGeneralError($message)
    {
        $this->_log->err($message);
        
        $view = new Zend_View();
        $view->assign(array(
            'errorMessage' => $message
        ));
        
        $view->headTitle('Error');
        
        $this->_renderView($view);
        exit();
    }


    /**
     * Renders the layout.
     *
     * @param Zend_View_Interface $view
     */
    protected function _renderView(Zend_View_Interface $view)
    {
        $layout = $this->_initLayout();
        $layout->setView($view);
        echo $layout->render();
    }


    /**
     * Returns true, if the debug mode is set.
     * 
     * @return boolean
     */
    protected function _isDebugMode()
    {
        return $this->_config->general->get('debug_mode', false);
    }


    /**
     * Generates a password based on the user's UID and the secret salt.
     *
     * @param AcLogin_RemoteUser $remoteUser
     * @return string
     */
    protected function _getPrincipalPassword(AcLogin_RemoteUser $remoteUser)
    {
        $uid = $remoteUser->getUid();
        $salt = $this->_getPasswordSalt();
        if (! $salt) {
            $this->_actionGeneralError('No salt!');
        }
        
        $base = $uid . $salt;
        
        return md5($base);
    }


    /**
     * Returns the salt to be used in user's password generation.
     * 
     * It may be a custom one or a value from the configuration.
     * 
     * @return string
     */
    protected function _getPasswordSalt()
    {
        if (NULL !== $this->_passwordSalt) {
            return $this->_passwordSalt;
        }
        
        return $this->_config->account->get('password_salt', NULL);
    }


    /**
     * Sets a custom password salt.
     * 
     * @param string $salt
     */
    protected function _setPasswordSalt($salt)
    {
        $this->_passwordSalt = $salt;
    }


    /**
     * Returns the layout directory.
     *
     * @return string
     */
    protected function _getLayoutPath()
    {
        return ACLOGIN_TPL_DIR . $this->_config->general->get('template', 'default') . DIRECTORY_SEPARATOR . 'layouts';
    }


    /**
     * Tries to determine the AC Login instance, if it is set.
     * 
     * @return string
     */
    protected function _getInstanceName()
    {
        if (preg_match('/instance\/([\w-]+)/', $_SERVER['REQUEST_URI'], $matches)) {
            return $matches[1];
        }
        
        if (isset($_GET['instance']) && preg_match('/^[\w-]+$/', $_GET['instance'])) {
            return $_GET['instance'];
        }
        
        return NULL;
    }


    /**
     * Returns the path of the config file.
     * 
     * If a certain instance is requested, the corresponding config file path is returned. Otherwise,
     * the standard config file path is returned.
     * 
     * @return string
     */
    protected function _getConfigFilePath()
    {
        /*
         * First, check if a special instance is required and if instances are configured.
         */
        $instanceName = $this->_getInstanceName();
        if (NULL !== $instanceName) {
            $instanceFile = ACLOGIN_CONFIG_DIR . 'instances.php';
            if (file_exists($instanceFile) && ($instances = require $instanceFile)) {
                if (! isset($instances[$instanceName])) {
                    $this->_die(sprintf("Unknown instance '%s'", $instanceName));
                }
                
                $instanceConfig = $instances[$instanceName];
                if (! isset($instanceConfig['config_file'])) {
                    $this->_die(sprintf("Instance '%s' without config file", $instanceName));
                }
                
                $instanceConfigFile = ACLOGIN_CONFIG_DIR . $instanceConfig['config_file'];
                
                if (! file_exists($instanceConfigFile)) {
                    $this->_die(sprintf("Non-existent config file '%s' for instance '%s'", $instanceConfigFile, $instanceName));
                }
                
                if (! is_readable($instanceConfigFile)) {
                    $this->_die(sprintf("Non-readable config file '%s' for instance '%s'", $instanceConfigFile, $instanceName));
                }
                
                return $instanceConfigFile;
            }
        }
        
        /*
         * Second, check if the 'config_file' options is set
         */
        if ($configFile = $this->getOption('config_file')) {
            return $configFile;
        }
        
        /*
         * At last, return the default config file path.
         */
        return ACLOGIN_CONFIG_DIR . $this->_configFileName;
    }


    /**
     * Handles non-recoverable error.
     * 
     * @param string $message
     */
    protected function _die($message = '')
    {
        if ($message) {
            error_log($message);
        }
        
        header("HTTP/1.0 404 Not Found");
        exit();
    }
}