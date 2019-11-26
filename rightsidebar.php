<!-- Right Sidebar -->
<div class="sidebar-right">
    <div id="right-sidebar-id">
        <div class="right-sidebar-header"><i class="fas fa-globe"></i>
            <span class="sidebar-header-text">Recently Online</span>
        </div>
        <div class="right-sidebar-section">
            <!-- recentlyOnline(minutes) -->
            <?php echo $adminfunctions->recentlyOnline(5); ?>
        </div>
        <div class="right-sidebar-header"><i class="fas fa-bolt"></i>
            <span class="sidebar-header-text">User Activity</span>
        </div>
        <div class="right-sidebar-section">
            <?php
            $result5 = $db->query("SELECT * FROM users WHERE username != '" . ADMIN_NAME . "' ORDER BY timestamp DESC LIMIT 1");
            $row5 = $result5->fetch();
            $lastlogin = $adminfunctions->displayDate($row5['timestamp']);
            echo $row5['username']." logged on - ".$lastlogin;
            ?>
        </div>        
        <div class="right-sidebar-header"><i class="fas fa-chart-line"></i>
            <span class="sidebar-header-text">Statistics</span>
        </div>
        <div class="right-sidebar-section">
            <?php
            $adminactivation = $adminfunctions->displayAdminActivation('regdate');
            $num_needact = $adminactivation->rowCount();
            echo "<p><i class='fas fa-check-circle'></i> There are ".$session->getNumMembers()." members.</p>";
            echo "<p><i class='fas fa-check-circle'></i> ".$num_needact . " accounts require activation.</p>";
            echo "<p><i class='fas fa-check-circle'></i> ".$adminfunctions->usersSince($session->username) . " new users have registered since your last visit.</p>";
            echo "<p><i class='fas fa-check-circle'></i> There are currently ".$functions->calcNumActiveUsers()." users and ".$session->calcNumActiveGuests()." guests online.</p>";
            echo "<p><i class='fas fa-check-circle'></i> Record Users Online : ".$configs->getConfig('record_online_users')."</p>";
            echo "<p><i class='fas fa-check-circle'></i> The last user to register was ".$adminfunctions->getLastUserRegisteredDetails(0) . " at ". $adminfunctions->displayDate($adminfunctions->getLastUserRegisteredDetails(1));
            ?>
        </div>
    </div>
</div>
<!-- END Right Sidebar -->
