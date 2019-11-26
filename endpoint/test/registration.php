<!DOCTYPE html>
<!-- -->
<html>
<head>
<meta charset="utf-8">
<title>Registration</title>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<?php
require('db.php');
if (isset($_REQUEST['username'])){
	$username = stripslashes($_REQUEST['username']);
	$username = mysqli_real_escape_string($con,$username); 
	$emailid = stripslashes($_REQUEST['email']);
	$emailid = mysqli_real_escape_string($con,$email);
	$password = stripslashes($_REQUEST['password']);
	$password = mysqli_real_escape_string($con,$password);
    $role = stripslashes($_REQUEST['role']);
	$role = mysqli_real_escape_string($con,$role);
        $query = "INSERT into `finforexusers` (username, password, emailid,role)
VALUES ('$username', '".md5($password)."', '$email', '$role')";
        $result = mysqli_query($con,$query);
        if($result){
            echo "<div class='form'>
<h3>You are registered successfully.</h3>
<br/>Click here to <a href='login.php'>Login</a></div>";
        }
    }else{
?>
	<form class="login" action="" method="post">
    <h1 class="login-title">Register</h1>
		<input type="text" class="login-input" name="username" placeholder="Username" required />
    <input type="text" class="login-input" name="email" placeholder="Email Adress">
    <input type="password" class="login-input" name="password" placeholder="Password">
    <input type="role" class="login-input" name="role" placeholder="Role">
    <input type="submit" name="submit" value="Register" class="login-button">
  <p class="login-lost">Already Registered? <a href="login.php">Login Here</a></p>
  </form>
 
<?php } ?>
</body>
</html>