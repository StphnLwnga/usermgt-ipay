<?php 
include("includes/controller.php");
$pagename = 'useradmin';
$container = '';
if(!$session->isAdmin()){
    header("Location: ".$configs->homePage());
    exit;
}
else{
?>
<!DOCTYPE html>
<html>
    <head>
        <title> User </title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="fonts/font-awesome/css/fontawesome-all.min.css" rel="stylesheet">

        <link href="css/navigation.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
        
        <!-- Datatables CSS -->
        <link href="css/plugins/datatables/dataTables.bootstrap.min.css" rel="stylesheet">        
        
    </head>
    <body>
        <!-- Page Wrapper -->
        <div id="page-wrapper">

            <!-- Side Menu -->
            <nav id="side-menu" class="navbar-default navbar-static-side" role="navigation">
                <div id="sidebar-collapse">
                    <div id="logo-element">
                        <a class="logo" href="index.php">
                            <span class="x-hidden">I</span><span class="logo-full">pay</span>
                        </a>
                    </div>
                    <?php include('navigation.php'); ?>
                </div>
            </nav>
            <!-- END Side Menu -->

            <?php include('top-navbar.php'); ?>      

            <!-- Page Content -->
            <div id="page-content" class="gray-bg">

                <!-- Title Header -->
                <div class="title-header white-bg">
                    <i class="fas fa-user"></i>
                    <h2>User Admin</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                        </li>
                        <li class="active">
                            User Admin
                        </li>
                    </ol>
                </div>
                <!-- END Title Header -->
                
                <div class="row">                                     
                    <div class="col-sm-12 col-md-12">
                        <div class="panel">
                            <div class="panel-body">
                                <h4><strong>User Admin</strong> - Manage your users. Click the tabs to see the other tables.</h4>
                            </div>
                        </div>
                    </div>                                     
                </div>
             
                <div class="row">   
                    <div class="col-sm-12 col-md-12">
                        <div class="panel">
                            <div class="panel-body">
                                <button href="#createUser" type="button" class="btn btn-main" data-toggle="modal">Create User</button>
                                <?php 
                                $stop = $adminfunctions->createStop($session->username, 'delete-inactive');
                                ?>
                                <a href="includes/adminprocess.php?form_submission=delete_inactive&stop=<?php echo $stop; ?>" class='btn btn-main confirmation' onclick="return confirm ('Are you sure you want to delete all users inactive for more than 30 days?')">Delete Inactive Users</span></a>
                            </div>
                        </div>
                    </div>
                </div>
             
                <div class="row">
                        <div class="col-sm-12 col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li role="presentation" class="active"><a href="#user_table" aria-controls="user_table" role="tab" data-toggle="tab">User Table</a></li>
                                        <li role="presentation"><a href="#users_activation" aria-controls="users_activation" role="tab" data-toggle="tab">Users Awaiting Activation</a></li>
                                        <li role="presentation"><a href="#current_sessions" aria-controls="current_sessions" role="tab" data-toggle="tab">Current Sessions</a></li>
                                    </ul>
                                </div>
                                <div class="panel-body">
                                    <!-- Tab panes -->
                                    <div class="tab-content">

                                        <div role="tabpanel" class="tab-pane active" id="user_table">
                                            <div class="panel">
                                                <div class="panel-heading">
                                                    <h2 class="panel-title">User's Table</h2>
                                                </div>
                                                <div class="panel-body table-responsive">
                                                    <table class="table table-striped table-bordered table-hover" id="dataTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Username</th>
                                                                <th>Status</th>
                                                                <th>E-mail</th>
                                                                <th>Registered</th>
                                                                <th>Last Login</th>
                                                                <th class='text-center'>View</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $sql = "SELECT * FROM users WHERE username != '" . ADMIN_NAME . "'";
                                                            $result = $db->prepare($sql);
                                                            $result->execute();
                                                            while ($row = $result->fetch()) {
                                                                $email = $row['email'];
                                                                $email = strlen($email) > 25 ? substr($email, 0, 25) . "..." : $email;
                                                                $lastlogin = $adminfunctions->displayDate($row['timestamp']);
                                                                $reg = $adminfunctions->displayDate($row['regdate']);

                                                                echo "<tr><td><a href='adminuseredit.php?usertoedit=" . $row['username'] . "'>" . $row['username'] . "</a></td>"
                                                                . "<td>" . $adminfunctions->displayStatus($row['username']) . "</td>"
                                                                . "<td><div class='shorten'><a href='mailto:" . $row['email'] . "'>" . $email . "</a></div></td>"
                                                                . "<td>" . $reg . "</td><td>" . $lastlogin . "</td>"
                                                                . "<td class='text-center'><div class='btn-group btn-group-xs'><a href='adminuseredit.php?usertoedit=" . $row['username'] . "' title='Edit' class='open_modal btn btn-default'><i class='fas fa-edit'></i> View</a></td>"
                                                                . "</tr>";
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <?php
                                        $orderby = 'regdate';
                                        $result2 = $adminfunctions->displayAdminActivation($orderby);
                                        ?>
                                        <div role="tabpanel" class="tab-pane" id="users_activation">
                                            <div class="panel">
                                                    <div class="panel-heading">
                                                        <h2 class="panel-title">Users Awaiting Activation</h2>
                                                    </div>
                                                    <div class="panel-body table-responsive">
                                                        <form class="form-horizontal" role="form" action="includes/adminprocess.php" method="POST">                                
                                                            <table class="table table-striped table-bordered table-hover" id="dataTable2">
                                                                <thead>
                                                                    <tr>
                                                                        <th><input type="checkbox" class="checkall"></th>
                                                                        <th>Username</th>
                                                                        <th>E-mail</th>
                                                                        <th>Registered</th>
                                                                        <th>IP Address</th>
                                                                        <th class='text-center'>View</th>                                                           
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    while ($row = $result2->fetch()) {
                                                                        $reg = $adminfunctions->displayDate($row['regdate']);
                                                                        $dbemail = $row['email'];
                                                                        $ip = $row['ip'];
                                                                        $email = strlen($dbemail) > 25 ? substr($dbemail, 0, 25) . "..." : $dbemail;
                                                                        echo "<tr>"
                                                                        . "<td><input name='user_name[]' type='checkbox' value='" . $row['username'] . "' /></td>"
                                                                        . "<td><a href='adminuseredit.php?usertoedit=" . $row['username'] . "'>" . $row['username'] . "</a></td>"
                                                                        . "<td><div class='shorten'><a href='mailto:" . $row['email'] . "'>" . $email . "</a></div></td>"
                                                                        . "<td>" . $reg . "</td>"
                                                                        . "<td>" . $ip . "</td>"
                                                                        . "<td class='text-center'><div class='btn-group btn-group-xs'><a href='adminuseredit.php?usertoedit=" . $row['username'] . "' title='Edit' class='open_modal btn btn-default'><i class='fas fa-edit'></i> View</a></td>"
                                                                        . "</tr>";
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                            <input type="hidden" name="form_submission" value="activate_users">
                                                            <button type="submit" id="submit" name="submit" class="btn btn-default"><i class="fas fa-sync-alt"></i> Activate Users</button>
                                                        </form>
                                                    </div>
                                                </div>       
                                        </div>

                                        <div role="tabpanel" class="tab-pane" id="current_sessions">
                                                <div class="panel">
                                                    <div class="panel-heading">
                                                        <h2 class="panel-title">Current Sessions</h2>
                                                    </div>
                                                    <div class="panel-body table-responsive">
                                                        <form class="form-horizontal" role="form" action="includes/adminprocess.php" method="POST">                                
                                                            <table class="table table-striped table-bordered table-hover" id="dataTable3">
                                                                <thead>
                                                                    <tr>
                                                                        <th><input type="checkbox" class="checkall"></th>
                                                                        <th>Username</th>
                                                                        <th>Last IP Address</th>
                                                                        <th>Last</th>
                                                                        <th>Expiry</th>                                                        
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                <?php 
                                                                $stop2 = $adminfunctions->createStop($session->username, 'delete-sessions');                             
                                                                $sql = "SELECT * FROM user_sessions ";
                                                                $result = $db->prepare($sql);
                                                                $result->execute();
                                                                while ($row = $result->fetch()) {
                                                                $userid = $row['userid'];
                                                                $id = $row['id'];
                                                                $username = $functions->getUserInfoSingularFromId('username', $userid);
                                                                $ipaddress = $row['ipaddress'];
                                                                $timestamp = $adminfunctions->displayDate($row['timestamp']);
                                                                $expires = $adminfunctions->displayDate($row['expires']);

                                                                echo "<tr>"
                                                                . "<td><input name='id[]' type='checkbox' value='" . $id . "' /></td>"
                                                                . "<td>" . $username . "</td>"
                                                                . "<td>" . $ipaddress . "</td>"
                                                                . "<td>" . $timestamp . "</td>"
                                                                . "<td>" . $expires . "</td>"
                                                                . "</tr>";
                                                                }
                                                                ?>
                                                                </tbody>
                                                            </table>
                                                            <input type="hidden" name="form_submission" value="delete_individual_sessions">
                                                            <input type="hidden" name="stop" value="<?php echo $stop2; ?>">
                                                            <button type="submit" id="submit2" name="submit" class="btn btn-default"><i class="fas fa-times"></i> Delete Selected</button>
                                                            <a href="includes/adminprocess.php?form_submission=delete_all_user_sessions&stop=<?php echo $stop2; ?>" class='btn btn-main confirmation' onclick="return confirm ('Are you sure you want to delete all existing user sessions?')">Kill All User Sessions</span></a>
                                                        </form>
                                                    </div>
                                                </div>   
                                        </div>

                                    </div>
                                </div>                               
                            </div>                           
                        </div>
                    </div>
                
                <!-- Modal -->
                <div class="modal fade" id="createUser" class="modal" tabindex="-1" role="dialog" aria-labelledby="createUser" aria-hidden="true">
                    <div class="modal-dialog">
                            <div class="modal-content" id="modal-content">
                                <form class="form-horizontal" id="admin-create-user" action="includes/adminprocess.php" method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title" id="myModalLabel">Create New User</h4>
                                    </div>
                                    <div class="modal-body">
                                        
                                        <div class="form-group <?php if (Form::error("user")) { echo 'has-error'; } ?>">
                                            <label for="inputUsername" class="col-sm-4 control-label">Username:</label>
                                            <div class="col-sm-7">
                                                <input name="user" type="text" class="form-control" id="inputUsername" placeholder="Username" value="<?php echo Form::value("user"); ?>">                            
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("user"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("firstname")){ echo 'has-error'; } ?> ">
                                            <label for="inputFirstname" class="col-sm-4 control-label">First Name:</label>
                                            <div class="col-sm-7">
                                                <input type="text" name="firstname" class="form-control" id="inputFirstname" placeholder="First Name" value="<?php echo Form::value("firstname"); ?>">                             
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("firstname"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("lastname")){ echo 'has-error'; } ?>">
                                            <label for="inputLastname" class="col-sm-4 control-label">Last Name:</label>
                                            <div class="col-sm-7">
                                                <input type="text" name="lastname" class="form-control" id="inputLastname" placeholder="Last Name" value="<?php echo Form::value("lastname"); ?>">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("lastname"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("pass")){ echo 'has-error'; } ?>">
                                            <label for="inputPassword" class="col-sm-4 control-label">New Password:</label>
                                            <div class="col-sm-7">
                                                <input type="password" name="pass" class="form-control" id="inputPassword" placeholder="New Password">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("pass"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("conf_newpass")){ echo 'has-error'; } ?>">
                                            <label for="confirmPassword" class="col-sm-4 control-label">Confirm Password:</label>
                                            <div class="col-sm-7">
                                                <input type="password" name="conf_pass" class="form-control" id="confirmPassword" placeholder="Confirm Password">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("pass"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("email")){ echo 'has-error'; } ?>">
                                            <label for="email" class="col-sm-4 control-label">E-mail:</label>
                                            <div class="col-sm-7">
                                                <input type="text" id="email" name="email" class="form-control" placeholder="Email" value="<?php echo Form::value("email"); ?>">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("email"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("email")){ echo 'has-error'; } ?>">
                                            <label for="conf_email" class="col-sm-4 control-label">Confirm E-mail:</label>
                                            <div class="col-sm-7">
                                                <input name="conf_email" type="text" id="conf_email" class="form-control" placeholder="Confirm Email" value="<?php echo Form::value("email"); ?>">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("email"); ?></small>
                                            </div>
                                        </div>

                                    <input type="hidden" name="form_submission" value="admin_registration">                                         

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" id="submit3" >Create New User</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- END Modal -->

                <footer> &copy; <?php echo date("Y"); ?> .</footer>

            </div>
            <!-- END Page Content -->

            <?php include('rightsidebar.php'); ?>

        </div>
        <!-- END Page Wrapper -->
        
        <!-- Scroll to top -->
        <a href="#" id="to-top" class="to-top"><i class="fas fa-angle-double-up"></i></a>

        <!-- JavaScript Resources -->
        <script src="js/jquery-2.1.3.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
        <script src="js/xavier.js"></script>
        
        <!-- Initialize Form Validation -->
        <script src="js/plugins/formValidation/useradminFormsValidation.js"></script>
        <script src="js/plugins/formValidation/jquery.validate.js"></script>
        <script>$(function() { FormsValidation.init(); });</script> 
        
        <!-- Datatables JS - https://cdn.datatables.net/ -->
        <script src="js/plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="js/plugins/datatables/dataTables.bootstrap.min.js"></script>
        
        <script>
            $(document).ready(function () {
                $('#dataTable').dataTable({
                    "columnDefs": [
                    { "width": "15px", "targets": 5 },
                    { "orderable": false, "targets": 5 }
                    ]
                });
            });

            $(document).ready(function () {
                $('#dataTable2').dataTable({         
                    "order": [[ 3, "desc"]],
                    "columnDefs": [
                    { "width": "15px", "targets": 0 },
                    { "width": "15px", "targets": 5 },
                    { "orderable": false, "targets": 0 },
                    { "orderable": false, "targets": 5 }                  
                    ]
                });
            });
           
            $(document).ready(function () {
                $('#dataTable3').dataTable({         
                    "order": [[ 1, "asc"]],
                    "columnDefs": [
                    { 
                        "width": "15px", "targets": 0,
                        "orderable": false, "targets": 0 }
                    ]
                });
            });
            
        </script>
        
        <script type="text/javascript">
            $(function() {
                // Javascript to enable link to tab
                var hash = document.location.hash;
                if (hash) {
                    console.log(hash);
                    $('.nav-tabs a[href='+hash+']').tab('show');
                }

                // Change hash for page-reload
                $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
                    window.location.hash = e.target.hash;
                });
             });
        </script>
        
        <!-- Check all (set for closest table) -->
        <script>
        $(function () {
            $('.checkall').on('click', function () {
            $(this).closest('table').find(':checkbox').prop('checked', this.checked);
            });
        });
        </script>   

    </body>
</html>
<?php
}
?>