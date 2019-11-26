<?php

class Session {

    public $username;     //Username given on sign-up
    public $userid;       //Random value generated on current login
    public $userlevel;    //The level to which the user pertains
    public $time;         //Time user was last active (page loaded)
    public $id;           //Users unique ID
    public $logged_in;    //True if user is logged in, false otherwise
    public $userinfo = array();  //The array holding all user info
    public $url;          //The page url current being viewed
    public $referrer;     //Last recorded site page viewed
    public $num_members;  //Number of signed-up users
    public $num_active_users;   //Number of active users viewing site
    public $num_active_guests;  //Number of active guests viewing site
    private $db;           //The Database Connection
    
    function __construct($db) {

        $this->db = $db;
        $this->functions = new Functions($db);
        $this->logger = new Logger($db);
        $this->configs = new Configs($db);
        $this->time = time();
        $this->startSession();

        $this->num_members = -1;

        /* Calculate number of users at site */
        $this->functions->calcNumActiveUsers();
        
        /* Calculate number of guests at site */
        $this->calcNumActiveGuests();

        // Calculates total users online each time a user visits/refreshes and adds to dbase if a record
        $total = $this->total_users_online = $this->functions->calcNumActiveUsers() + $this->calcNumActiveGuests();
        if ($total > $this->configs->getConfig('record_online_users')) {
            $this->configs->updateConfigs($total, 'record_online_users');
            $this->configs->updateConfigs($this->time, 'record_online_date');
        }
    }

    /* **************************************************************************************
     * startSession - Performs all the actions necessary to initialise this session object.
     * **************************************************************************************
     */
    function startSession() {

        session_start();   // Tell PHP to start the session

        /* Determine if user is logged in */
        $this->logged_in = $this->checkIfLoggedIn();

        // Set guest value to users not logged in, and update active guests table accordingly.
        if (!$this->logged_in) {
            $this->username = $_SESSION['username'] = GUEST_NAME;
            $this->userlevel = 0;
            $this->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);
            
        // Logged In - but need to check if session has expired   
        } else {
            $this->checkExpiryTimes($_SESSION['session_id']); // Check if session has already expired
            $this->updateActiveUsers($this->id); // If Not, update timestamps
        }

        /* Remove inactive visitors from database */
        $this->removeInactiveGuests();

        /* Set referrer page */
        if (isset($_SESSION['url'])) {
            $this->referrer = $_SESSION['url'];
        } else {
            $this->referrer = "/";
        }

        /* Set current url */
        $this->url = $_SESSION['url'] = htmlentities($_SERVER['PHP_SELF']);
    }

    /* *************************************************************************************************
     * checkIfLoggedIn - Checks if the user has already previously logged in, and a session established.
     * *************************************************************************************************
     */
    function checkIfLoggedIn() {       

        /* Check if user has remember me cookie then check details */
        if (isset($_COOKIE['xa_sessid']) && isset($_COOKIE['xa_valid'])) {
            $this->functions->cookieCheck($_COOKIE['xa_sessid'], $_COOKIE['xa_valid']);
        }

        /* Username and userid have been set and not guest */
        if (isset($_SESSION['username']) && isset($_SESSION['session_id']) && isset($_SESSION['id']) && $_SESSION['username'] != GUEST_NAME) {
            /* Confirm that username and userid are valid */
            if ($this->confirmUserID($_SESSION['session_id'], $_SESSION['id']) != 0) {
                /* Variables are incorrect, user not logged in */
                unset($_SESSION['username']);
                unset($_SESSION['session_id']);
                unset($_SESSION['id']);
                return false;
            }
            
            /* Check if user is banned whilst logged on and then kill their sessions */
            if ($this->functions->checkBanned($_SESSION['username'])) {
                return false;
            }

            /* User is logged in, set class variables */
            $this->userinfo = $this->functions->getUserInfo($_SESSION['username'], $this->db);
            $this->username = $this->userinfo['username'];
            $this->id = $this->userinfo['id'];
            $this->userlevel = $this->userinfo['userlevel'];
            return true;
        }
        /* User not logged in */ else {
            return false;
        }
    }

    /* **************************************************************************************************
     * confirmUserID - Checks whether or not the given username is in the database, if so it checks if the 
     * given userid is the same userid in the database for that user.
     * **************************************************************************************************
     */
    function confirmUserID($session_id, $userid) {
        /* Verify that user is in database */
        $query = "SELECT session_id FROM user_sessions WHERE session_id = '$session_id' AND userid = '$userid'";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':userid' => $userid, ':sessionid' => $session_id));
        $count = $stmt->rowCount();

        if (!$stmt || $count < 1) {
            return 1; // Indicates username failure  
        }

        $dbarray = $stmt->fetch();

        /* Validate that userid is correct */
        if ($session_id == $dbarray['session_id']) {
            return 0; // Success! Username and userid confirmed
        } else {
            return 2; // Indicates userid invalid
        }
    }
    
    /* *****************
     * checkExpiryTimes
     * *****************
     */
    function checkExpiryTimes($session_id) {
        
        // Time in Minutes that the user's session will expire. PHP Server will override this setting!
        $timeout = time() - $this->configs->getConfig('USER_TIMEOUT') * 60;
        
        // Select timestamp from session table and compare against timeout unless 'Remember Me' set
        $sql = $this->db->query("SELECT timestamp FROM user_sessions WHERE session_id = '$session_id'");
        $sessioninfo = $sql->fetch();
                
        if ($sessioninfo['timestamp'] < $timeout && !isset($_COOKIE['xa_sessid']) && !isset($_COOKIE['xa_valid'])) {
            $this->logout();
            header("Location: " . $this->configs->loginPage()); // Better redirection?
        }
        
    }
    
    /* ***************************************************************
     * updateActiveUsers - This is run on every page change / refresh. 
     * ***************************************************************
     */
    function updateActiveUsers($userid) {  
        
        $time = time();
        $session_id = $_SESSION['session_id'];
        $ipaddress = $_SERVER['REMOTE_ADDR'];
        
        // Update Users table with timestamp of activity - in multi user scenario, timestamp in users table gets updated by all logged on accounts.
        $stmt2 = $this->db->prepare("UPDATE users SET timestamp = '$time' WHERE id = :userid");
        $stmt2->execute(array(':userid' => $userid));
        
        // Update Session table with timestamp of activity and ipaddress
        $this->db->query("UPDATE user_sessions SET timestamp = '$time', ipaddress = '$ipaddress' WHERE session_id = '$session_id'");

        $this->functions->calcNumActiveUsers();    
    }

    /* ********************
     * removeInactiveGuests 
     * ********************
     */
    function removeInactiveGuests() {
        $timeout = time() - $this->configs->getConfig('GUEST_TIMEOUT') * 60;
        $stmt = $this->db->prepare("DELETE FROM active_guests WHERE timestamp < $timeout");
        $stmt->execute();
        $this->calcNumActiveGuests();
    }

    /* *************************************************************************************************************
     * calcNumActiveGuests - Finds out how many active guests are viewing site and sets class variable accordingly.
     * *************************************************************************************************************
     */
    function calcNumActiveGuests() {
        /* Calculate number of GUESTS at site */
        $sql = $this->db->query("SELECT * FROM active_guests");
        return $num_active_guests = $sql->rowCount();
    }   

    /* ******************
     * removeActiveGuest
     * ******************
     */
    function removeActiveGuest($ip) {
        $sql = $this->db->prepare("DELETE FROM active_guests WHERE ip = '$ip'");
        $sql->execute();
        $this->calcNumActiveGuests();
    }

    /* **************************************************************************************
     * checkUserEmailMatch - Checks whether username and email match in forget password form.
     * **************************************************************************************
     */
    function checkUserEmailMatch($username, $email) {
        $query = "SELECT username FROM users WHERE username = :username AND email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':username' => $username, ':email' => $email));
        $number_of_rows = $stmt->rowCount();

        if (!$stmt || $number_of_rows < 1) {
            return 0;
        } else {
            return 1;
        }
    }

    /* **********************************************************************
     * getNumMembers - Returns the number of signed-up users of the website.
     * **********************************************************************
     */
    function getNumMembers() {
        if ($this->num_members < 0) {
            $result = $this->db->query("SELECT id FROM users");
            $this->num_members = $result->rowCount();
        }
        return $this->num_members;
    }

    /* **************************************************************************************************************
     * logout - Gets called when the user wants to be logged out of the website. It deletes any 'remember me' cookies 
     * that were stored on the users computer, and also unsets session variables and demotes his user level to guest.
     * **************************************************************************************************************
     */
    function logout() {       

        // Delete Remember Me cookies if they exist.
        $this->functions->deleteCookies();
        
        /* Update Log */
        //if log turned on.. do the following...
        if(!empty($this->id)) { $this->logger->logAction($this->id, 'LOGOFF'); }
        
        /* Delete current session */
        $this->functions->deleteCurrentSession();

        /* Unset PHP session variables */
        unset($_SESSION['username']);
        unset($_SESSION['session_id']);

        /* Reflect fact that user has logged out */
        $this->logged_in = false;       

        // Remove from active users table and add to active guests tables.       
        $this->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);      

        /* Set user level to guest */
        $this->username = GUEST_NAME;
        $this->userlevel = GUEST_LEVEL;

        /* Destroy session */
        session_regenerate_id();
        session_destroy();

    }

    /* **********************************************************************************************
     * editAccount - Attempts to edit the user's account information including the password, which it 
     * first makes sure is correct if entered, if so and the new password is in the right format, the 
     * change is made. All other fields are changed automatically.
     * **********************************************************************************************
     */
    function editAccount($subcurpass, $subnewpass, $subconfnewpass, $subemail) {

        /* New password entered */
        if ($subnewpass) {

            /* Current Password error checking */
            $field = "curpass";  //Use field name for current password
            
            if (!$subcurpass) {
                Form::setError($field, "Current Password not entered");
            // Check current password matches
            } else if ($this->confirmUserPass($this->username, $subcurpass) != 0) {
                Form::setError($field, "Current Password incorrect");
            }

            /* New Password error checking */
            $field = "newpass";  //Use field name for new password
            
            /* check minimum password length (default 8 characters) */
            if (strlen($subnewpass) < $this->configs->getConfig('min_pass_chars')) {
                Form::setError($field, "New Password too short");
            }
            /* check maximum password length (in an attempt to stop DOS attack for extra long password) */
            else if (strlen($subnewpass) > $this->configs->getConfig('max_pass_chars')) {
                Form::setError($field, "New Password too long");
            }
            /* Check if passwords match */ 
            else if ($subnewpass != $subconfnewpass) {
                Form::setError($field, "Passwords do not match");
            }
        }
        /* Current password entered but new one not */ else if ($subcurpass) {
            $field = "newpass";  //Use field name for new password
            Form::setError($field, "New Password not entered");
        } else if ($subconfnewpass) {
            $field = "conf_newpass";  //Use field name for new password
            Form::setError($field, "Current Password not entered");
        }
        
        //Checks E-mail Address - $subemail is there twice on purpose
        $this->currentemail = $this->functions->getUserInfoSingular('email', $this->username);
        if($this->currentemail != $subemail){
            $this->functions->emailCheck($subemail, $subemail, 'email');
        }

        /* Errors exist, have user correct them */
        if (Form::$num_errors > 0) {
            return false;  //Errors with form
        }

        /* Update password since there were no errors */
        if ($subcurpass && $subnewpass) {
            $subnewpass = password_hash($subnewpass, PASSWORD_DEFAULT);
            $this->functions->updateUserField($this->username, "password", $subnewpass);
            $this->functions->deleteAllSessionsButCurrent($this->id);
            
            /* Update Log */
            $this->logger->logAction($this->id, 'PWD_CHANGE'); 
        }

        /* Change Email */
        if ($subemail) {
            $change = $this->functions->updateUserField($this->username, "email", $subemail);
            
            if($change){
            /* Update Log */
            $this->logger->logAction($this->id, 'EMAIL_CHANGE');
            }
        }

        /* Success! */
        return true;
    }
    
    /* ********************************************************************************************************************
     * confirmUserPass - Checks whether or not the given username is in the database, if so it checks if the given password 
     * is the same password in the database for that user. If the user doesn't exist or if the passwords don't match up, 
     * it returns an error code (1 or 2). On success it returns 0.
     * ********************************************************************************************************************
     */
    function confirmUserPass($username, $password) {
        /* Verify that user is in database */
        $query = "SELECT password, userlevel FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':username' => $username));
        $count = $stmt->rowCount();
        if (!$stmt || $count < 1) {
            return 1; // Indicates username failure
        }

        /* Retrieve password and userlevel from result, strip slashes */
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
    
    /**
     * ****************************************************************************************
     * isAdmin - Returns true if currently logged in user is an administrator, false otherwise.
     * ****************************************************************************************
     */
    function isAdmin() {
        return ($this->userlevel == ADMIN_LEVEL || $this->userlevel == SUPER_ADMIN_LEVEL);
    }
    
    /* ****************************************************************************************************
     * isSuperAdmin - Returns true if currently logged in user is THE Super Administrator, false otherwise.
     * ****************************************************************************************************
     */
    function isSuperAdmin() {
        return ($this->userlevel == SUPER_ADMIN_LEVEL and
                $this->username == ADMIN_NAME);
    }
    
    /* *********************************************************************************************
     * isMemberOfGroup - Returns true if currently logged in user is a member of a certain group.
     * *********************************************************************************************
     */
    function isMemberOfGroup($groupname) {
        $userid = $this->id;
        $group_id = $this->functions->getGroupId($groupname);
        $sql = $this->db->query("SELECT user_id FROM users_groups WHERE group_id = '$group_id' AND user_id = '$userid' LIMIT 1");
        return $groupinfo = $sql->fetchColumn();
    }
    
    /* *******************************************************************************************************************
     * isMemberOfGroupOverLevel - Returns true if currently logged in user is a member of a group over the specified level
     * *******************************************************************************************************************
     */
    function isMemberOfGroupOverLevel($level) {
        $userid = $this->id;
        $sql = $this->db->query("SELECT groups.group_level, groups.group_id, users_groups.group_id, users_groups.user_id FROM `groups` INNER JOIN `users_groups` ON groups.group_id=users_groups.group_id WHERE users_groups.user_id = '$userid' AND groups.group_level > '$level'");
        $count = $sql->rowCount();
        return ($count > 0);
    }

    /* *************************************************************************************************
     * isUserlevel - Returns true if currently logged in user is at a certain userlevel, false otherwise.
     * *************************************************************************************************
     */
    function isUserlevel($level) {
        return ($this->userlevel == $level);
    }

    /* *****************************************************************************************************
     * overUserlevel - Returns true if currently logged in user is over a certain userlevel, false otherwise.
     * *****************************************************************************************************
     */
    function overUserlevel($level) {
        if ($this->userlevel > $level) {
            return true;
        } else {
            return false;
        }
    }

    /* **************************************************
     * addActiveGuest - Adds guest to active guests table
     * ************************************************** 
     */
    function addActiveGuest($ip, $time) {
        $sql = $this->db->prepare("REPLACE INTO active_guests VALUES ('$ip', '$time')");
        $sql->execute();
        $this->calcNumActiveGuests();
    }
    
    /* **********************
     * generatePasswordLink 
     * ********************** 
     */
    function generatePasswordLink($id) {
        $templink = Functions::generateRandStr(18);
        $time = time();
        $sql = $this->db->query("DELETE FROM user_temp WHERE userid = '$id'");
        $sql2 = $this->db->prepare("INSERT INTO user_temp (userid, timestamp, type, detail) VALUES (:userid, '$time', 'pwd', '$templink')");
        $sql2->execute(array(':userid' => $id));
        if($sql2) { return $templink; } else { return 'error'; }
    }
    
    /* *****************************************
     * activateUser - Process to activate Users
     * *****************************************
     */
    function activateUser($user, $actkey){
        
        $id = $this->functions->getUserInfoSingular('id', $user);
        $time = time() - 1209600; // 2 weeks
        $activation = $this->db->prepare("SELECT * FROM user_temp WHERE detail = :actkey AND userid = '$id' AND timestamp >= '$time' LIMIT 1");
        $activation->execute(array(':actkey' => $actkey));
        $count = $activation->rowCount();
        
        if(!$count){
            echo "The Link is incorrect or no longer valid!";
            exit();
        }
        
        // Check first act key matches and it belongs to userid, and it not expired?!!!
        
        $userlevel = $this->db->prepare("SELECT userlevel FROM users WHERE username = :user LIMIT 1");
        $userlevel->execute(array(':user' => $user));
        $row = $userlevel->fetch();

        // Checks if account needs activating (1 is email activation - 2 is admin activation)
        if (($row['userlevel'] == 1) or ( $row['userlevel'] == 2)) {

            $sql = $this->db->prepare("UPDATE users SET USERLEVEL = '3' WHERE username=:user");
            $sql->bindParam(":user", $user);
            $sql->execute();

            //Checks if successful
            $count = $sql->rowCount();

            if ($count) {

                //Display Activation Success message and send e-mail confirming.
                $mailer = new Mailer($this->db, $this->configs);
                if ($row['userlevel'] == 2) {
                    echo "<div>You have activated the account for " . $user . ".</div>";
                } else {
                    echo "<div>Your account is now activated.</div>";
                }
                $sql = $this->db->query("SELECT email FROM users WHERE username = '$user'");
                $email_array = $sql->fetch();
                $email = array_shift($email_array);
                $mailer->adminActivated($user, $email);

                //Generate new activation key so old e-mail cannot change userlevel at a later date
                $sql2 = $this->db->prepare("DELETE FROM user_temp WHERE detail = :actkey AND userid = '$id'");
                $sql2->execute(array(':actkey' => $actkey));
            } else {
                echo "<div>Your account was not activated. Please contact Admin for more assistance.</div>";
            }
        } else if (($row['userlevel'] != 1 ) && ($row['actkey'] === $actkey)) {
            echo "<div>This account does not need activating.</div>";
        } else {
            echo "<div>An error has occured. Please contact Admin for more assistance.</div>";
        }
    }

}
