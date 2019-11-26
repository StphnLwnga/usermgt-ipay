<?php

class Login {

    private $db;

    public function __construct($db, $session, $functions, $configs, $logger) {
        $this->db = $db;
        $this->session = $session;
        $this->functions = $functions;
        $this->configs = $configs;
        $this->logger = $logger;
        $this->time = time();
    }

    /* *********************************************************************************************
     * login - The user has submitted his username and password through the login form, this 
     * function checks the authenticity of that information in the database and creates the session.
     * *********************************************************************************************
     */
    function login($subuser, $subpass, $subremember) {
        
        /* Username checking */
        $field = "username";  //Use field name for username
        if (!$subuser || strlen($subuser = trim($subuser)) == 0) {
            Form::setError($field, "Username or Email not entered");
        }

        /* Password checking */
        $field = "password";  //Use field name for password
        if (!$subpass) {
            Form::setError($field, "Password not entered");
        }

        /* Return if form errors exist */
        if (Form::$num_errors > 0) {
            $_SESSION['value_array'] = $_POST;
            $_SESSION['error_array'] = Form::getErrorArray();
            return false;
        }

        /* Checks that username/email is in database and password is correct */
        $result = $this->confirmUserPass($subuser, $subpass);
        $field = "username";

        /* Check error codes */
        if ($result == 1) {
            // Username doesn't match
            Form::setError($field, "Login is invalid. Please try again");
        } else if ($result == 2) {
            // Password incorrect
            Form::setError($field, "Login is invalid. Please try again");
            // The next section checks the database for failed login attempts and delays the login screen by that number. 
            $num_of_attemps = $this->addLoginAttempt($subuser);
            if ($num_of_attemps > 10) {
                // Up to a maximum of 10 seconds
                $num_of_attemps = 10;
            }
            sleep($num_of_attemps);
        } else if ($result == 3) {
            Form::setError($field, "Your account has not been activated yet");
        } else if ($result == 4) {
            Form::setError($field, "Your account has not been activated by admin yet");
        } else if ($result == 5) {
            Form::setError($field, "Your user account has been banned");
        } else if ($result == 6) {
            Form::setError($field, "Your IP address has been banned");
        }

        /* Return if form errors exist */
        if (Form::$num_errors > 0) {
            $_SESSION['value_array'] = $_POST;
            $_SESSION['error_array'] = Form::getErrorArray();
            return false;
        }

        /* Get Users details from the Users table and put in to userinfo object */
        $this->userinfo = $this->getUserInfo($subuser, $this->db);
        
        /* Username and password correct, register session variables */
        $this->username = $_SESSION['username'] = $this->userinfo['username'];
        $this->id = $_SESSION['id'] = $this->userinfo['id'];
        $this->session_id = $_SESSION['session_id'] = Functions::generateRandStr(16);    

        /* Create hashed validator ready for Session (used for Remember Me) 
         *  The $validator variable (unhashed) is saved to the cookie, whereas a hashed version is saved to the database. 
         *  If somehow the user_sessions table is leaked, immediate widespread user impersonation is prevented.
         */
        $validator = Functions::generateRandStr(32);
        $hashedValidator = hash('sha256', $validator);
        
        /* Set session expire using database config */
        $cookie_expire = $this->configs->getConfig('COOKIE_EXPIRE');
        $session_expire_timestamp = time() + 60 * 60 * 24 * $cookie_expire;
        
        /* Check if Remember Me Set? */
        if ($subremember) { 
                $persist = 1;
                $cookie_expire = $this->configs->getConfig('COOKIE_EXPIRE');
                $session_expire_timestamp = time() + 60 * 60 * 24 * $cookie_expire;               
        } else { 
                $persist = 0;
                $session_expire = $this->configs->getConfig('USER_TIMEOUT');
                $session_expire_timestamp = time() + 60 * 60 * 24 * $session_expire; 
        }
        
        /* Add Session to Sessions Table */
        $this->functions->addSession($this->session_id, $hashedValidator, $persist, $this->id, $session_expire_timestamp);          
        
        /* Update activity and cleanup */
        $this->functions->updateUserField($this->username, "lastip", $_SERVER['REMOTE_ADDR']);    
        $this->functions->addLastVisit($this->username);
        $this->session->updateActiveUsers($this->username);
        $this->resetLoginAttempts($this->username);
        $this->session->removeActiveGuest($_SERVER['REMOTE_ADDR']);
               
        /* Update Log */
        $this->logger->logAction($this->id, 'LOGIN');

        /* Set Remember Me Cookies - They expire on the time set in the database */
        if ($subremember) {
            $cookie_path = $this->configs->getConfig('COOKIE_PATH');
            setcookie("xa_sessid", $this->session_id, time() + 60 * 60 * 24 * $cookie_expire, $cookie_path,"","",1);
            setcookie("xa_valid", $validator, time() + 60 * 60 * 24 * $cookie_expire, $cookie_path,"","",1);
        }
        
        if($this->configs->getConfig('ALLOW_MULTI_LOGONS') == 0){
            $this->functions->deleteAllSessionsButCurrent($this->id);
        }
        
        session_regenerate_id();

        /* Login completed successfully */
        return true;
    }

    /* *****************************************************************************************************************************************
     * confirmUserPass - Checks whether the given username is in the database, and then matches the given password with the one in the database. 
     * If the user doesn't exist or if the passwords don't match up, it returns an error code (1 or 2). On success it returns 0.
     * *****************************************************************************************************************************************
     */
    function confirmUserPass($username, $password) {

        $query = "SELECT password, userlevel FROM users WHERE (username = :username OR email = :username)";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':username' => $username));
        $count = $stmt->rowCount();
        
        if (!$stmt || $count < 1) {
            return 1; // Indicates username failure
        }

        /* Retrieve password and userlevel from result */
        $dbarray = $stmt->fetch();

        if (!password_verify($password, $dbarray['password'])) {
            return 2; // Indicates password failure
        }

        /* Password correct - now check status */
        if ($dbarray['userlevel'] == 1) {
            return 3; // Indicates account has not been activated
        }

        if ($dbarray['userlevel'] == 2) {
            return 4; // Indicates admin has not activated account
        }

        if ($this->functions->checkBanned($username)) {
            return 5; // Indicates account is banned
        }

        if ($this->functions->ipDisallowed($_SERVER['REMOTE_ADDR'])) {
            return 6; // Indicates IP address is banned
        } else {
            return 0; // All good!
        }
    }

    /* ********************************************************************************
     * getUserInfo - Returns all the info from the database regarding the chosen user.
     * ********************************************************************************
     */
    public function getUserInfo($username) {
        $query = "SELECT * FROM users WHERE (username = :username OR email = :username)";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':username' => $username));
        $dbarray = $stmt->fetch();
        /* Error occurred, return given name by default */
        $result = count($dbarray);
        if (!$dbarray || $result < 1) {
            return NULL;
        }
        /* Return result array */
        return $dbarray;
    }

    /* ********************************************************************************
     * The next 3 functions are to do with failed login attempts and adding a timeout
     * period of up to a maximum of 10 seconds in order to help avoid brute force attacks.
     * ********************************************************************************
     */
    public function addLoginAttempt($username) {
        $num_of_attempts = (($num_of_attempts = $this->getLoginAttempts($username)) + 1);
        $this->db->query("UPDATE users SET user_login_attempts = '$num_of_attempts' WHERE (username = '$username' OR email = '$username')");
        return $num_of_attempts;
    }

    // Failed login attempts is reset to zero on successful login
    public function resetLoginAttempts($username) {
        $this->db->query("UPDATE users SET user_login_attempts = '0' WHERE (username = '$username' OR email = '$username')");
    }

    public function getLoginAttempts($username) {
        $stmt = $this->db->query("SELECT user_login_attempts FROM users WHERE (username = '$username' OR email = '$username')");
        return $login_attempts = $stmt->fetchColumn();
    }

}
