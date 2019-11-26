<?php
include_once 'controller.php';

function passwordChange($session, $functions, $db) {
    $password = $_POST['password'];
    $conf_password = $_POST['confirm_password'];
    $userid = $_POST['userid'];
    $id = $_POST['id'];
    $user = $functions->getUserInfoSingularFromId('username', $userid);
    
    if($password != $conf_password){
        $field = "password";
        Form::setError($field, "Passwords do not match!");
    }
    
    /* Errors exist, have user correct them */
    if (Form::$num_errors > 0) {
        $pwdkey = $_POST['pwdkey'];
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer . "?key=" . $pwdkey);
    } else {
        $newpass = password_hash($password, PASSWORD_DEFAULT);
        $functions->updateUserField($user, "password", $newpass);
        $db->query("DELETE FROM user_temp WHERE id = '$id' AND type = 'pwd' LIMIT 1");
        $_SESSION['password_changed'] = 1;
        header("Location: " . $session->referrer);
    }
    
}

passwordChange($session, $functions, $db);