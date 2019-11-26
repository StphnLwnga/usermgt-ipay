<?php
include_once 'controller.php';

/* Process.php - The Process page simplifies the task of processing user submitted forms. Also handles the logout procedure. */

if (isset($_POST['form_submission'])) {

    $form_submission = $_POST['form_submission'];
    switch ($form_submission) {

        case "adminlogin" :
            adminLogin($db, $session, $functions, $configs, $logger);
            break;
        case "login" :
            login($db, $session, $functions, $configs, $logger);
            break;
        case "register" :
            register($db, $session, $configs, $functions, $logger);
            break;
        case "forgot_password" :
            forgotPass($db, $session, $configs, $functions);
            break;
        case "edit_account" :
            editAccount($session);
            break;
        default :
            if ($session->logged_in) {
                logout($session, $configs);
            } else {
                header("Location: " . $configs->homePage());
            }
    }
} else {
    logout($session, $configs);
}

/**
 * *************************************************************************
 * adminLogin - Admin process for logging in the admin to the control 
 * *************************************************************************
 */
function adminLogin($db, $session, $functions, $configs, $logger) {
    $login = new Login($db, $session, $functions, $configs, $logger);
    /* Login attempt */
    $success = $login->login($_POST['username'], $_POST['password'], isset($_POST['remember']));

    /* Login successful */
    if ($success) {
        header("Location: " . $configs->homePage());
    } else {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer);
    }
}

/**
 * *******************************************************************************************************
 * login - Processes the user submitted login form, if errors are found, the user is redirected to correct 
 * the information, if not, the user is effectively logged in to the system.
 * *******************************************************************************************************
 */
function login($db, $session, $functions, $configs, $logger) {
    $login = new Login($db, $session, $functions, $configs, $logger);
    /* Login attempt */
    $success = $login->login($_POST['username'], $_POST['password'], isset($_POST['remember']));

    /* Login successful */
    if ($success) {
        if(isset($_GET['path'])) {
            if($_GET['path'] == 'admin') {
                $path = $configs->homePage();
                header("Location: " . $path);
            } else if($_GET['path'] == 'referrer') {
                $path = $session->referrer;
                header("Location: " . $path);
            }    
            // else if($_GET['path'] == 'example') {
            //    $path = 'http://www.example.com';
            //    header("Location: " . $path);
            // }
        /* Individual User Homepage Set? */    
        } else if($configs->getConfig('TURN_ON_INDIVIDUAL') == 1) {
            $functions->setIndividualPath();
        } else {
            $path = $configs->loginPage();
            header("Location: " . $path);
        }
    /* Login failed */
    } else {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer);
    }
}

/**
 * ************************************************************************************************************
 * logout - Simply attempts to log the user out of the system given that there is no logout form to process.
 * ************************************************************************************************************
 */
function logout($session, $configs) {
    $session->logout();
    header("Location: " . $configs->homePage());
}

/**
 * **********************************************************************************************************************
 * register - Processes the user submitted registration form, if errors are found, the user is redirected to correct the
 * information, if not, the user is effectively registered with the system and an email is (optionally) sent to the newly
 * created user.
 * **********************************************************************************************************************
 */
function register($db, $session, $configs, $functions, $logger) {
    $registration = new Registration($db, $session, $configs, $functions, $logger);

    /* Checks if registration is disabled */
    if ($configs->getConfig('ACCOUNT_ACTIVATION') == 4) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 6;
        header("Location: " . $session->referrer);
    }

    /* Is Google reCaptcha enabled and does it pass */
    if ($configs->getConfig('ENABLE_CAPTCHA')){
        $url = 'https://www.google.com/recaptcha/api/siteverify';
	$data = array(
		'secret' => '6Lf4nUkUAAAAAIYifHORg7UePTdCw6ggIY7j_p_V',
		'response' => $_POST["g-recaptcha-response"]
	);
        $query = http_build_query($data);
        $options = array(
            'http' => array(
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($query)."\r\n".
                    "User-Agent:MyAgent/1.0\r\n",
            'method'  => "POST",
            'content' => $query,
            ),
        );
	$context  = stream_context_create($options);
	$verify = file_get_contents($url, false, $context);
	$captcha_success = json_decode($verify);
        if ($captcha_success->success == false) {
            $field = "recaptcha"; 
            Form::setError($field, "You failed the recaptcha");
            header("Location: " . $session->referrer . "#register");
        }
    }

    /* Convert username to all lowercase (by option) */
    if ($configs->getConfig('ALL_LOWERCASE') == 1) {
        $_POST['user'] = strtolower($_POST['user']);
    }

    /* Hidden form field captcha deisgned to catch out auto-fill spambots */
    if (!empty($_POST['killbill'])) {
        $retval = 2;
    } else {
        /* Registration attempt */
        $retval = $registration->register($_POST['user'], $_POST['firstname'], $_POST['lastname'], $_POST['pass'], $_POST['conf_pass'], $_POST['email'], $_POST['conf_email'], 0);
    }

    /* Registration Successful */
    if ($retval == 0) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 0;
        header("Location: " . $session->referrer . "#register");
    }

    /* E-mail Activation */ 
    else if ($retval == 3) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 3;
        header("Location: " . $session->referrer . "#register");
    }

    /* Admin Activation */ 
    else if ($retval == 4) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 4;
        header("Location: " . $session->referrer . "#register");
    }

    /* No Activation Needed but E-mail going out */ 
    else if ($retval == 5) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 5;
        header("Location: " . $session->referrer . "#register");
    }

    /* Error found with form */ 
    else if ($retval == 1) {
        header("Location: " . $session->referrer . "#register");
    }

    /* Registration attempt failed */ 
    else if ($retval == 2) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 2;
        header("Location: " . $session->referrer . "#register");
    }
}

/**
 * ***************************************************************************************************
 * forgotPass - Validates the given username and email then if everything is fine, a new password is 
 * generated and emailed to the address the user gave on sign up.
 * ***************************************************************************************************
 */
function forgotPass($db, $session, $configs, $functions) {
    /* Username error checking */
    $user = $_POST['user'];
    $email = $_POST['email'];

    if (!$user || strlen($user = trim($user)) == 0) {
        $field = "pwd_user";  //Use field name for username
        Form::setError($field, "Username not entered<br>");
    } else if(!$email || strlen($email = trim($email)) == 0) {
        $field = "pwd_email";  //Use field name for username
        Form::setError($field, "Email Address not entered<br>");
    } else {
        $field = "pwd_user";  //Use field name for username
        if (strcasecmp($user, ADMIN_NAME) == 0) {
            Form::setError($field, "The password for this account cannot be reset");
        } else if (strlen($user) < $configs->getConfig('min_user_chars') || strlen($user) > $configs->getConfig('max_user_chars')) {
            Form::setError($field, "Username or E-mail is incorrect<br>");
        } else if ((!$functions->usernameRegex($user)) || (!$functions->usernameTaken($user, $db))) {
            Form::setError($field, "Username or E-mail is incorrect<br>");
        } else if ($session->checkUserEmailMatch($user, $email) == 0) {
            Form::setError($field, "Username or E-mail is incorrect<br>");
        }
    }

    /* Errors exist, have user correct them */
    if (Form::$num_errors > 0) {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
    } else {
        $mailer = new Mailer($db, $configs);
        
        /* Get email of user */
        $emailaddress = $functions->getUserInfoSingular('email', $user);
        $id = $functions->getUserInfoSingular('id', $user);      
        
        /* Get temp link to send to email and add to database */
        $templink = $session->generatePasswordLink($id);
        
        /* Attempt to send reset password link to email address of user */
        if ($mailer->sendPassLink($user, $emailaddress, $templink)) {
            
            /* Email success, display success message */ 
            $_SESSION['sentpassemail'] = true;
        } else {
            
            /* Email failure, display error */ 
            $_SESSION['sentpassemail'] = false;
        }
    }
    header("Location: " . $session->referrer . "#reset");
}

/**
 * ********************************************************************************************************************
 * editAccount - Attempts to edit the user's account information, including the password, which must be verified before 
 * a change is made.
 * ********************************************************************************************************************
 */
function editAccount($session) {

    /* Account edit attempt */
    $form = new Form();
    $retval = $session->editAccount($_POST['curpass'], $_POST['newpass'], $_POST['conf_newpass'], $_POST['email']);

    /* Account edit successful */
    if ($retval) {
        $_SESSION['useredit'] = true;
        header("Location: " . $session->referrer);
    }

    /* Error found with form */ 
    else {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer . "#link-edit-user");
    }
}
