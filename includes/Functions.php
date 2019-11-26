<?php

class Functions {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /* **********************************************************************************
     * getUserInfo - Returns the result array from an sql query asking for all 
     * information stored regarding the given username. If query fails, NULL is returned.
     * **********************************************************************************
     */
    public function getUserInfo($username) {
        $query = "SELECT * FROM users WHERE username = :username";
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

    /* *************************************************************************************
     * getUserInfoSingular - Returns the single user's info using the username as a variable.
     * *************************************************************************************
     */
    public function getUserInfoSingular($asset, $username) {
        $asset = strip_tags($asset);
        $query = "SELECT $asset FROM users WHERE (username = :username OR email = :username)";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':username' => $username));
        return $usersinfo = $stmt->fetchColumn();
    }
    
    /* *************************************************************************************
     * getUserInfoSingularFromId - Returns the single user's info using the ID as a variable.
     * *************************************************************************************
     */
    public function getUserInfoSingularFromId($asset, $id) {
        $asset = strip_tags($asset);
        $query = "SELECT $asset FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':id' => $id));
        return $usersinfo = $stmt->fetchColumn();
    }

    /* *********************************************************************************************
     * usernameTaken - Returns true if the username has been taken by another user, false otherwise.
     * *********************************************************************************************
     */
    public function usernameTaken($username) {
        $result = $this->db->query("SELECT username FROM users WHERE username = '$username'");
        $count = $result->rowCount();
        return ($count > 0);
    }

    /* ******************************************************************
     * ipDisallowed - Returns true if the ip address has been disallowed.
     * ******************************************************************
     */
    function ipDisallowed($ip) {
        $query = "SELECT ban_id FROM banlist WHERE ban_ip = :ip_address";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':ip_address' => $ip));
        $count = $stmt->rowCount();
        return ($count > 0);
    }

    /*
     * *******************************************************************************************************
     * updateUserField - Updates a field, specified by the field parameter, in the user's row of the database.
     * *******************************************************************************************************
     */
    public function updateUserField($username, $field, $value) {
        $query = "UPDATE users SET " . $field . " = :value WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':username' => $username, ':value' => $value));
        return $count = $stmt->rowCount();
    }
    
    /* *******************************************************
     * addSession - Adds new active session to database table
     * *******************************************************
     */
    public function addSession($userid, $validator, $persist, $id, $expires) {
        $addsession = $this->db->prepare("INSERT INTO user_sessions (session_id, hashedValidator, persistent, userid, expires) VALUES (:userid, :validator, :persist, :id, :expires)");
        $addsession->execute(array(':userid' => $userid, ':validator' => $validator, ':persist'=> $persist, ':id' => $id, ':expires' => $expires));
        return $count = $addsession->rowCount();
    }

    /* ***************************************************************************
     * addLastVisit - Updates the database with the users previous visit timestamp.
     * ***************************************************************************
     */
    public function addLastVisit($username) {
        $admin_details = $this->getUserInfo($username);
        $admin_lastvisit = $admin_details['timestamp'];
        $this->updateUserField($username, "previous_visit", $admin_lastvisit);
    }

    /* *********************************************************************************
     * checkBanned - Returns true if the username has been banned by the administrator.
     * *********************************************************************************
     */
    function checkBanned($username) {
        $userid = $this->getUserInfoSingular('id', $username);
        $query = "SELECT ban_userid FROM banlist WHERE ban_userid = :userid";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':userid' => $userid));
        $count = $stmt->rowCount();
        return ($count > 0);
    }
    
    /* ***********************************************************************
     * setIndividualPath - Checks whether it is turned on and sets the path.
     * ***********************************************************************
     */
    function setIndividualPath() {
        $configs = new Configs($this->db);
        if($configs->getConfig('HOME_SETBYADMIN') == 1) { //Home page set by admin
            $path = $configs->getConfig('USER_HOME_PATH');
            $path = str_replace('%username%', $_POST['username'], $path);   
            // Admin?
            if((strtolower($_POST['username']) == strtolower(ADMIN_NAME)) && ($configs->getConfig('NO_ADMIN_REDIRECT') == 1)) {
                $path = $configs->loginPage();
                header("Location: " . $path);
            } else { 
                header("Location: " . $configs->getConfig('WEB_ROOT') . $path);
            }
        } else if ($configs->getConfig('HOME_SETBYADMIN') == 0) { //Home page set in users profile
            // Admin?
            if((strtolower($_POST['username']) == strtolower(ADMIN_NAME)) && ($configs->getConfig('NO_ADMIN_REDIRECT') == 1)) {
                $path = $configs->loginPage();
                header("Location: " . $path);
            } else {
                $username = $_POST['username'];
                $query = "SELECT user_home_path FROM users WHERE username = :username";
                $stmt = $this->db->prepare($query);
                $stmt->execute(array(':username' => $username));
                $path = $stmt->fetchColumn();
                $path = str_replace('%username%', $_POST['username'], $path);
                header("Location: " . $configs->getConfig('WEB_ROOT') . $path);
            }
        }
    }

    /* *********************************************************************************************************
     * calcNumActiveUsers - Finds out how many active users are viewing site and sets class variable accordingly.
     * *********************************************************************************************************
     */
    public function calcNumActiveUsers() {
        $ten = time() - 600; // 20 mins
        $sql = $this->db->query("SELECT id FROM user_sessions WHERE timestamp >= '$ten'");
        return $num_active_users = $sql->rowCount();
    }
    
    /* *****************************************************************************************************************************
     * activeUserList - Shows a list of logged on users seperated by a comma, and with links to their userprofile IF the $showlinks
     * parameter is set to 1. User must have refreshed session in last 20 minutes.
     * *****************************************************************************************************************************
     */
    public function activeUserList($showlinks) {
        $twenty = time() - 1200; // 20 mins
        $stmt = $this->db->query("SELECT users.username FROM `users` INNER JOIN `user_sessions` ON users.id=user_sessions.userid WHERE user_sessions.timestamp >= '$twenty' ORDER BY users.username ASC");
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $username = $row['username'];
            if($count != 0) { echo ","; }
            if($showlinks){
                $configs = new Configs($this->db);
                echo " <a href='".$configs->getConfig('WEB_ROOT')."adminuseredit.php?usertoedit=" . $username . "'>" . $username . "</a>";
            } else {
                echo " $username";
            }
            $count++;
        }
    }
    
    /* ***************************************************
     * totalUsers - Finds out how many users are members
     * ***************************************************
     */
    public function totalUsers() {
        /* Calculate number of USERS at site */
        $sql = $this->db->query("SELECT id FROM users");
        return $total_users = $sql->rowCount();
    }

    /* ****************************************************************************************
     * usernameRegex - checks which regex is needed - returns false if the username fails the 
     * selected regex. The regex is set in the configuration table in the database.
     * ****************************************************************************************
     */
    public function usernameRegex($subuser) {
        $configs = new Configs($this->db);
        $option = $configs->getConfig('USERNAME_REGEX');
        switch ($option) {
            case 'any_chars':
                $regex = '.+';
                break;

            case 'alphanumeric_only':
                $regex = '[A-Za-z0-9]+';
                break;

            case 'alphanumeric_spacers':
                $regex = '[A-Za-z0-9-[\]_+ ]+';
                break;

            case 'any_letter_num':
                $regex = '[a-zA-Z0-9]+';
                break;

            case 'letter_num_spaces':
            default:
                $regex = '[-\]_+ [a-zA-Z0-9]+';
                break;
        }
        if (preg_match('#^' . $regex . '$#u', $subuser)) {
            return 1;
        }
    }
    
    /* ***********************************************************
     * emailCheck - Checks email address on registration / change
     * ***********************************************************
     */
    function emailCheck($email, $conf_email, $field) {

        if (!$email || strlen($email = trim($email)) == 0) {
            Form::setError($field, "Email not entered");
        } else {
            /* Check if valid email address using PHPs filter_var */
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Form::setError($field, "Email invalid");
            }
            /* Check if emails match, not case-sensitive */ 
            else if (strcasecmp($email, $conf_email)) {
                Form::setError($field, "Email addresses do not match");
            }
            /* Check if email is already in use */ 
            else if ($this->emailTaken($email)) {
                Form::setError($field, "Email address already registered");               
            } 

            /* Convert email to all lowercase */
            $email = strtolower($email);
        }
    }

    /* ***********************************************
     * nameCheck - Checks firstname & lastname fields
     * ***********************************************
     */
    function nameCheck($name, $field, $fullname, $min, $max) {
        if (!$name) {
            Form::setError($field, $fullname . " not entered");
        } else {

            /* Check if field is too short */
            if (strlen($name) < $min) {
                Form::setError($field, $fullname . " too short");
            }
            /* Check if field is too long */ else if (strlen($name) > $max) {
                Form::setError($field, $fullname . " too long");
            }
            /* Check if field is not alphanumeric */ else if (!preg_match("#^[A-Za-z0-9-[\]_+ ]+$#u", ($name = trim($name)))) {
                Form::setError($field, $fullname . " not alphanumeric");
            }
        }
    }
    
    /* **************************************************************************************
     * emailTaken - Returns true if the email has been taken by another user, false otherwise.
     * **************************************************************************************
     */
    function emailTaken($email) {
        $query = "SELECT email FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $count = $stmt->rowCount();
        return ($count > 0);
    }

    /* ******************************************
     * Functions to do with Group administration.
     * ******************************************
     */
    function checkGroupNumbers($groupid) {
        $sql = $this->db->query("SELECT COUNT(group_id) FROM users_groups WHERE group_id = '$groupid'");
        return $count = $sql->fetchColumn();      
    }
    
    function getGroupId($groupname) {
        $sql = $this->db->query("SELECT users_groups.group_id FROM groups INNER JOIN `users_groups` ON groups.group_id = users_groups.group_id WHERE group_name = '$groupname' LIMIT 1");
        return $group_id = $sql->fetchColumn();
    }
    
    function returnGroupInfo($id) {
        $sql = $this->db->query("SELECT * FROM `groups` WHERE group_id = '$id'");
        return $groupinfo = $sql->fetch();
    }
    
    function returnGroupMembers($id) {
        $sql = $this->db->query("SELECT users.username, users_groups.group_id FROM `users` INNER JOIN `users_groups` ON users.id=users_groups.user_id WHERE users_groups.group_id = '$id'");
        return $groupinfo = $sql->fetch();
    }
    
    /* ************
     * Cookie Check
     * ************
     */
    function cookieCheck($session_id, $validator) {
        $hashedValidator = hash('sha256', $validator);
        $time = time();
        $sql = $this->db->query("SELECT * FROM user_sessions WHERE session_id = '$session_id'");
        $sessioninfo = $sql->fetch();
        if($sessioninfo){
            if(hash_equals($sessioninfo['hashedValidator'], $hashedValidator) && $sessioninfo['expires'] > $time){
                $userinfo = $this->getUserInfoSingularFromId('username', $sessioninfo['userid']);
                $_SESSION['username'] = $userinfo;
                $_SESSION['session_id'] = $session_id;
                $_SESSION['id'] = $sessioninfo['userid'];
                
                
                $configs = new Configs($this->db);
                
                // Check if Remember Me Cookie and subsequent session should expire or be renewed.
                if ($configs->getConfig('PERSIST_NOT_EXPIRE') == '1') {

                    // Extend expiry date of Remember Me Cookies
                    $cookie_path = $configs->getConfig('COOKIE_PATH');
                    $cookie_expire = $configs->getConfig('COOKIE_EXPIRE');
                    setcookie("xa_sessid", $session_id, time() + 60 * 60 * 24 * $cookie_expire, $cookie_path,"","",1);
                    setcookie("xa_valid", $validator, time() + 60 * 60 * 24 * $cookie_expire, $cookie_path,"","",1);

                    // Extend expiry date of session
                    $session_expiry = time() + 60 * 60 * 24 * $cookie_expire;               
                    $this->db->query("UPDATE user_sessions SET expires = '$session_expiry' WHERE session_id = '$session_id'");
                }

            } else {
                // Session matched but either the validator didnt or the session expired
                $this->deleteSession($sessioninfo['id']);
                $this->deleteCookies();
                session_regenerate_id();
                session_destroy();
                unset($_SESSION['session_id']);
                unset($_SESSION['id']);
                $_SESSION['username'] = GUEST_NAME;
            }
        } else {
            // No session match
            $this->deleteCookies();
            session_regenerate_id();
            session_destroy();
            unset($_SESSION['session_id']);
            unset($_SESSION['id']);
            $_SESSION['username'] = GUEST_NAME;
        }
        
    }
    
    /* *************************************************************
     * deleteCookies - This function deletes the Remember Me Cookies
     * *************************************************************
     */
    function deleteCookies(){
        $configs = new Configs($this->db);
        if (isset($_COOKIE['xa_sessid']) && isset($_COOKIE['xa_valid'])) {

            $cookie_expire = $configs->getConfig('COOKIE_EXPIRE');
            $cookie_path = $configs->getConfig('COOKIE_PATH');

            setcookie("xa_sessid", "", time() - 60 * 60 * 24 * $cookie_expire, $cookie_path);
            setcookie("xa_valid", "", time() - 60 * 60 * 24 * $cookie_expire, $cookie_path);
        }
    }
    
    /* ****************************
     * Session Management Functions
     * ****************************
     */
    function deleteSession($id) {
        $deletesess = $this->db->prepare("DELETE FROM user_sessions WHERE id = :id LIMIT 1");
        $deletesess->execute(array(':id' => $id));
    }
    
    function deleteAllUserSessions($userid) {
        $deletesess = $this->db->prepare("DELETE FROM user_sessions WHERE userid = :userid");
        $deletesess->execute(array(':userid' => $userid));
    }
    
    function deleteAllSessionsButCurrent($userid) {
        $sessionid = $_SESSION['session_id'];
        $deletesess = $this->db->prepare("DELETE FROM user_sessions WHERE userid = :userid AND session_id != '$sessionid'");
        $deletesess->execute(array(':userid' => $userid));
    }
    
    function deleteAllSessionsButAdmin() {
        $sessionid = $_SESSION['session_id'];
        $this->db->query("DELETE FROM user_sessions WHERE session_id != '$sessionid'");
    }
    
    function deleteCurrentSession() {
        $sessionid = $_SESSION['session_id'];
        $this->db->query("DELETE FROM user_sessions WHERE session_id = '$sessionid'");
    }

    /* ***************************************************************************
     * generateRandStr - Generates a string made up of randomized letters (lower 
     * and upper case) and digits, the length is a specified parameter.
     * ***************************************************************************
     */
    public static function generateRandStr($length) {
        
        $string = bin2hex(openssl_random_pseudo_bytes($length));  
        return $string;
    }

}
