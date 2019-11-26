<?php

class Registration {

    private $db;
    private $session;

    public function __construct($db, $session, $configs, $functions, $logger) {
        $this->db = $db;
        $this->session = $session;
        $this->configs = $configs;
        $this->functions = $functions;
        $this->logger = $logger;
    }

    /* ****************************************************************************************************************************
     * register - Gets called when the user has just submitted the registration form. Determines if there were any errors with the 
     * entry fields, if so, it records the errors and returns to the form. If no errors were found, it registers the new user and 
     * returns a success code depending on what type of activation it is. It returns 2 if registration failed.
     * ****************************************************************************************************************************
     */
    function register($user, $firstname, $lastname, $pass, $conf_pass, $email, $conf_email, $isadmin) {
        /* Username error checking */
        $field = "user";  //Use field name for username
        if (!$user || strlen($user = trim($user)) == 0) {
            Form::setError($field, "Username not entered");
        } else {
            /* check length */
            if (strlen($user) < $this->configs->getConfig('min_user_chars')) {
                Form::setError($field, "Username below " . $this->configs->getConfig('min_user_chars') . " characters");
            } else if (strlen($user) > $this->configs->getConfig('max_user_chars')) {
                Form::setError($field, "Username above " . $this->configs->getConfig('max_user_chars') . " characters");
            }
            /* Check username contains correct characters (Regex is set in configs) */ 
            else if (!$this->functions->usernameRegex($user)) {
                Form::setError($field, "Username does not match requirements");
            }
            /* Check if username is reserved */ 
            else if (strcasecmp($user, GUEST_NAME) == 0) {
                Form::setError($field, "Username reserved word");
            }
            /* Check if username is already in use */ 
            else if ($this->usernameTaken($user, $this->db)) {
                Form::setError($field, "Username already in use");
            }
            /* Check if username is disallowed */ 
            else if ($this->usernameDisallowed($user)) {
                Form::setError($field, "Username Disallowed");
            }
            /* Check if IP address is banned */ 
            else if ($this->functions->ipDisallowed($_SERVER['REMOTE_ADDR'])) {
                Form::setError($field, "IP Address banned");
            }
        }

        /* Firstname error checking */
        $this->functions->nameCheck($firstname, 'firstname', 'First Name', 2, 30);

        /* Lastname error checking */
        $this->functions->nameCheck($lastname, 'lastname', 'Last Name', 2, 30);
        
        /* Email error checking */
        $this->functions->emailCheck($email, $conf_email, 'email');

        /* Password error checking */
        $field = "pass";  //Use field name for password
        if (!$pass) {
            Form::setError($field, "Password not entered");
        } else {
            /* Check length */
            if (strlen($pass) < $this->configs->getConfig('min_pass_chars')) {
                Form::setError($field, "Password too short");
            }
            /* Check if password is too long */ 
            else if (strlen($pass) > $this->configs->getConfig('max_pass_chars')) {
                Form::setError($field, "Password too long");
            }
            /* Check if passwords match */ 
            else if ($pass != $conf_pass) {
                Form::setError($field, "Passwords do not match");
            }
        }    

        /* Errors exist, have user correct them */
        if (Form::$num_errors > 0) {
            $_SESSION['value_array'] = $_POST;
            $_SESSION['error_array'] = Form::getErrorArray();
            return 1;  //Errors with form
        }
        /* No errors, add the new account to the database */ 
        else {          
            if ($this->addNewUser($user, $firstname, $lastname, $pass, $email)) {
                
                $mailer = new Mailer($this->db, $this->configs);
                $time = time();

                /* Check Account activation setting and process accordingly. */

                /* E-mail Activation */
                if (($this->configs->getConfig('ACCOUNT_ACTIVATION') == 2) AND ( $isadmin != '1')) {
                    $token = Functions::generateRandStr(16);                  
                    $mailer->sendActivation($user, $email, $token, $this->configs);
                    $id = $this->functions->getUserInfoSingular('id', $user);
                    $this->db->query("INSERT INTO user_temp SET userid = '$id', timestamp = '$time', type = 'act', detail = '$token'");
                    $successcode = 3;
                }

                /* Admin Activation */ 
                else if (($this->configs->getConfig('ACCOUNT_ACTIVATION') == 3) AND ( $isadmin != '1')) {
                    $token = Functions::generateRandStr(16);
                    $mailer->adminActivation($user, $email, $this->configs);
                    $mailer->activateByAdmin($user, $email, $token);
                    $id = $this->functions->getUserInfoSingular('id', $user);
                    $this->db->query("INSERT INTO user_temp SET userid = '$id', timestamp = '$time', type = 'act', detail = '$token'");
                    $successcode = 4;
                }

                /* No Activation Needed but E-mail going out */ 
                else if (($this->configs->getConfig('EMAIL_WELCOME') && $this->configs->getConfig('ACCOUNT_ACTIVATION') == 1 ) AND ( $isadmin != '1')) {
                    $mailer->sendWelcome($user, $email, $this->configs);
                    $successcode = 5;

                /* No Activation Needed and NO E-mail going out */
                } else {
                    $successcode = 0;
                }
                
                /* Update Log */
                if($isadmin == '1'){
                    $id = $this->session->id;
                    $this->logger->logAction($id, "REGISTERED : ".$user );
                } else {
                    $id = $this->functions->getUserInfoSingular('id', $user);
                    $this->logger->logAction($id, "REGISTERED" );
                }
                
                return $successcode;  //New user added succesfully
            } else {
                return 2;  //Registration attempt failed
            }
        }
    }

    /* *********************************************************************************************
     * usernameTaken - Returns true if the username has been taken by another user, false otherwise.
     * *********************************************************************************************
     */
    function usernameTaken($username) {
        $result = $this->db->query("SELECT username FROM users WHERE username = '$username'");
        $count = $result->rowCount();
        return ($count > 0);
    }

    /* ***********************************************************************
     * usernameDisallowed - Returns true if the username has been disallowed.
     * ***********************************************************************
     */
    function usernameDisallowed($username) {
        $query = "select * from banlist where :username like concat('%',ban_username,'%') AND ban_username != ''";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $count = $stmt->rowCount();
        return ($count > 0);
    }

    /* ***********************************************************************************
     * addNewUser - Inserts the given (username, password, email) info into the database. 
     * Appropriate user level is set. Returns true on success, false otherwise.
     * ***********************************************************************************
     */
    function addNewUser($username, $firstname, $lastname, $password, $email) {
        $time = time();
        /* If admin sign up, give admin user level */
        if (($this->functions->totalUsers() == '0') AND (strcasecmp($username, ADMIN_NAME) == 0)) {
            $ulevel = SUPER_ADMIN_LEVEL;
        
       /* Which validation is on? */
        } else if ($this->configs->getConfig('ACCOUNT_ACTIVATION') == 1) {
            $ulevel = REGUSER_LEVEL; /* No activation required */
        } else if ($this->configs->getConfig('ACCOUNT_ACTIVATION') == 2) {
            $ulevel = ACT_EMAIL; /* Activation e-mail will be sent */
        } else if ($this->configs->getConfig('ACCOUNT_ACTIVATION') == 3) {
            $ulevel = ADMIN_ACT; /* Admin will activate account */
        } else if (($this->configs->getConfig('ACCOUNT_ACTIVATION') == 4) && !$this->session->isAdmin()) {
            header("Location: " . $this->configs->homePage()); /* Registration Disabled so go back to Home Page */
        } else {
            $ulevel = REGUSER_LEVEL;
        }

        /* Hash password using PHP's inbuilt password_hash function - currently using BCRYPT - as of 2.5 */ 
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $userip = $_SERVER['REMOTE_ADDR'];

        $query = "INSERT INTO users SET username = :username, firstname = :firstname, lastname = :lastname, password = :password, userlevel = $ulevel, email = :email, timestamp = $time, ip = '$userip', regdate = $time";
        $stmt = $this->db->prepare($query);
        return $stmt->execute(array(':username' => $username, ':firstname' => $firstname, ':lastname' => $lastname, ':password' => $password_hash, ':email' => $email));
    }

}
