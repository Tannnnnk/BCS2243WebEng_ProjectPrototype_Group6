<?php
require_once '../db_connection.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
    /* Student styles */
    .student body { display: flex; flex-direction: column; height: 100vh; background-color: #f4f7f6; color: #333; }
        
    .student .top-bar { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; width: 100%; height: 70px; z-index: 1000; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; }
    .student .system-title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; display: flex; align-items: center; gap: 12px; }
    .student .system-logo { width: 36px; height: 36px; background-color: white; color: #10b981; border-radius: 8px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
    .student .user-profile-section { display: flex; align-items: center; gap: 20px; }
    .student .profile-group { display: flex; align-items: center; gap: 12px; }
    .student .top-bar-photo { width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); border-radius: 50%; border: 2px solid white; display: flex; justify-content: center; align-items: center; overflow: hidden; font-size: 14px; font-weight: bold; }
    .student .welcome-text { font-size: 15px; font-weight: bold; }
        
    .student .role-badge { background-color: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; }
    .student .main-layout { display: flex; flex: 1; overflow: hidden; }
    
    .student .sidebar { position: fixed; height: calc(100vh - 70px); overflow-y: auto; box-sizing: border-box; top: 70px; width: 250px; background-color: white; border-right: 1px solid #e2e8f0; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
    .student .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 1px; }
    .student .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; }
    .student .sidebar a:hover { background-color: #f8fafc; color: #10b981; }
    .student .sidebar a.active { background-color: #e6ffed; color: #10b981; border-left: 4px solid #10b981; }
    .student .logout-btn { margin-top: auto; border-top: 1px solid #e2e8f0; color: #ef4444 !important; text-align: center; }

    .student .content-area { flex: 1; padding: 40px; overflow-y: auto; width: calc(100% - 250px); margin-left: 250px; padding-top: 90px; min-height: 100vh; box-sizing: border-box;}

    /* Committee styles */
    .committee body { display: flex; flex-direction: column; height: 100vh; background-color: #fffaf0; color: #333; }
    
    .committee .top-bar { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; width: 100%; height: 70px; z-index: 1000; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; }
    .committee .system-title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; display: flex; align-items: center; gap: 12px; }
    .committee .system-logo { width: 36px; height: 36px; background-color: #fff; color: #ea580c; border-radius: 8px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 5px rgba(234,88,12,0.3); }
    
    .committee .user-profile-section { display: flex; align-items: center; gap: 20px; }
    .committee .profile-group { display: flex; align-items: center; gap: 12px; }
    .committee .top-bar-photo { width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); border-radius: 50%; border: 2px solid white; display: flex; justify-content: center; align-items: center; overflow: hidden; font-size: 14px; font-weight: bold; color: white; }
    .committee .welcome-text { font-size: 15px; font-weight: bold; }
    
    .committee .role-badge { background-color: rgba(255,255,255,0.25); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; color: white;}
    .committee .main-layout { display: flex; flex: 1; overflow: hidden; }

    .committee .sidebar { position: fixed; height: calc(100vh - 70px); overflow-y: auto; box-sizing: border-box; top: 70px; width: 250px; background-color: white; border-right: 1px solid #fed7aa; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
    .committee .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #ea580c; font-weight: bold; letter-spacing: 1px; }
    .committee .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
    .committee .sidebar a:hover { background-color: #fff7ed; color: #ea580c; }
    .committee .sidebar a.active { background-color: #ffedd5; color: #ea580c; border-left: 4px solid #ea580c; }
    .committee .logout-btn { margin-top: auto; border-top: 1px solid #fed7aa; color: #ef4444 !important; text-align: center; justify-content: center;}

    .committee .content-area { flex: 1; padding: 40px; overflow-y: auto; width: calc(100% - 250px); margin-left: 250px; padding-top: 90px; min-height: 100vh; box-sizing: border-box; }
</style>

<?php if ($active_role === 'Student'): ?>

    <div class="student">
        <div class="top-bar">
            <div class="system-title">
                <div class="system-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                     </svg>
                </div>
                FK Student Club & Event Management
            </div>

            <div class="user-profile-section">
                <div class="profile-group">
                    <div class="top-bar-photo">
                        <?php if (!empty($photo_path)): ?>
                            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($stu_name, 0, 1)); ?>
                        <?php endif; ?>
                     </div>
                    <div class="welcome-text">Welcome, <?php echo htmlspecialchars($stu_name); ?></div>
                </div>
                <div class="role-badge">Role: <?php echo htmlspecialchars($active_role); ?></div>
            </div>
        </div>

        <div class="main-layout">
            <div class="sidebar">
                <div class="sidebar-title">Main Menu</div>
                
                <a href="student_dashboard.php" class="<?= ($current_page == 'student_dashboard.php') ? 'active' : '' ?>">Dashboard</a>
                <a href="profile_dashboard.php" class="<?= ($current_page == 'profile_dashboard.php') ? 'active' : '' ?>">My Profile</a>       
                <a href="club_directory.php" class="<?= ($current_page == 'club_directory.php' || $current_page == 'club_details.php') ? 'active' : '' ?>">Club Directory</a>
                <a href="event_directory.php" class="<?= ($current_page == 'event_directory.php' || $current_page == 'browse_event.php') ? 'active' : '' ?>">Event Directory</a>
                <a href="student_participation_dashboard.php" class="<?= ($current_page == 'student_participation_dashboard.php') ? 'active' : '' ?>">My Participation</a>
                
                <a href="../logout.php" class="logout-btn">LogOut</a>
            </div>

<?php else: ?>   

    <div class="committee">
        <div class="top-bar">
            <div class="system-title">
                <div class="system-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                     </svg>
                </div>
                FK Student Club & Event Management
            </div>

            <div class="user-profile-section">
                <div class="profile-group">
                    <div class="top-bar-photo">
                        <?php if (!empty($photo_path)): ?>
                            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($stu_name, 0, 1)); ?>
                        <?php endif; ?>
                     </div>
                    <div class="welcome-text">Welcome, <?php echo htmlspecialchars($stu_name); ?></div>
                </div>
                <div class="role-badge">Role: <?php echo htmlspecialchars($active_role); ?></div>
            </div>
        </div>

        <div class="main-layout">
            <div class="sidebar">
                <div class="sidebar-title">Main Menu</div>
                
                <a href="committee_dashboard.php" class="<?= ($current_page == 'committee_dashboard.php') ? 'active' : '' ?>">Dashboard</a>
                <a href="profile_dashboard.php" class="<?= ($current_page == 'profile_dashboard.php') ? 'active' : '' ?>">My Profile</a>
                <a href="committee_details.php" class="<?= ($current_page == 'committee_details.php') ? 'active' : '' ?>">My Committee Details</a> 
                <a href="event_directory.php" class="<?= in_array($current_page, ['event_directory.php', 'create_event.php', 'manage_events.php', 'view_event_details.php', 'manage_attendance.php', 'report_page.php', 'assign_committee.php', 'engagement_trend.php']) ? 'active' : '' ?>">Event Directory</a>
                <a href="attendance_search_event.php" class="<?= ($current_page == 'attendance_search_event.php' || $current_page == 'attendance_update.php') ? 'active' : '' ?>">Manage Attendance</a>
                
                <a href="../logout.php" class="logout-btn">LogOut</a>
            </div>
            
<?php endif; ?>