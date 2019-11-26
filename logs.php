<?php 
include("includes/controller.php");
$pagename = 'logs';
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
        <title> user</title>
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
                            <span class="x-hidden">S</span><span class="logo-full">lash</span>
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
                    <i class="fas fa-chart-bar"></i>
                    <h2>Logs</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                        </li>
                        <li class="active">
                            Logs
                        </li>
                    </ol>
                </div>
                <!-- END Title Header -->
             
                <div class="row">                                     
                    <div class="col-md-9 col-lg-10">
                        <div class="panel">
                            <div class="panel-heading">
                                <h2 class="panel-title">Logs</h2>
                            </div>
                            <div class="panel-body table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTable">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Event</th>
                                                <th>Date / Time</th>
                                                <th>IP Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT * FROM log_table ORDER BY timestamp DESC";
                                            $result = $db->prepare($sql);
                                            $result->execute();
                                            while ($row = $result->fetch()) {
                                                
                                                $username = $functions->getUserInfoSingularFromId('username', $row['userid']);

                                                echo "<tr>";
                                                echo "<td>$username</td>";
                                                echo "<td>".$row['log_operation']."</td>";
                                                echo "<td>".$adminfunctions->displayDate($row['timestamp'])."</td>";
                                                echo "<td>".$row['ip']."</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <div class="panel">
                            <div class="panel-body">
                            <form action="includes/logprocess.php" id="user-groups-edit" class="form-horizontal" method="post">
                                <input type="Submit" class="btn btn-main" value="Delete All Logs" onclick="return confirm ('Are you sure you want to delete all the logs, this cannot be undone?')">
                                <input type="hidden" name="form_submission" value="delete_logs">
                            </form>
                                <br>
                            <form action="includes/logprocess.php" id="user-groups-edit" class="form-horizontal" method="post">
                                <input type="Submit" class="btn btn-main" value="Delete Logs (> 30 days)" onclick="return confirm ('Are you sure you want to delete some logs, this cannot be undone?')">
                                <input type="hidden" name="form_submission" value="delete_some_logs">
                            </form>
                            </div>
                        </div>
                    </div>
                </div>

                <footer> &copy; <?php echo date("Y"); ?> <</footer>

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
        
        <!-- Datatables JS - https://cdn.datatables.net/ -->
        <script src="js/plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="js/plugins/datatables/dataTables.bootstrap.min.js"></script>
        
       <script>
            $(document).ready(function () {
                $('#dataTable').dataTable({         
                "order": [[ 2, "desc"]],
                "pageLength": 25
                });
            });
        </script>       

    </body>
</html>
<?php
}
?>