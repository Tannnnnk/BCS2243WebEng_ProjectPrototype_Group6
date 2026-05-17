<?php
require_once '../db_connection.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
    body { display: flex; flex-direction: column; height: 100vh; background-color: #f4f7f6; color: #333; }
        
    .top-bar {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white;
        padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10;
    }
    .system-title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; display: flex; align-items: center; gap: 12px; }
    .system-logo {
        width: 36px; height: 36px; background-color: #10b981; color: white;
        border-radius: 8px; display: flex; justify-content: center; align-items: center;
    }
        
    .user-profile-section { display: flex; align-items: center; gap: 20px; }
    .profile-group { display: flex; align-items: center; gap: 12px; }
        
    /* Photo added back! */
    .top-bar-photo {
        width: 40px; height: 40px; background-color: #334155; 
        border-radius: 50%; border: 2px solid #10b981;
        display: flex; justify-content: center; align-items: center; 
        overflow: hidden; font-size: 14px; font-weight: bold; color: white;
    }

    .welcome-text { font-size: 15px; font-weight: bold; }
    .role-badge { background-color: #10b981; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; color: white;}

    .main-layout { display: flex; flex: 1; overflow: hidden; }
    .sidebar { width: 250px; background-color: white; border-right: 1px solid #e2e8f0; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
    .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 1px; }
        
    .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; }
    .sidebar a:hover { background-color: #f8fafc; color: #0f172a; }
    .sidebar a.active { background-color: #f1f5f9; color: #0f172a; border-left: 4px solid #0f172a; }
    .logout-btn { margin-top: auto; border-top: 1px solid #e2e8f0; color: #ef4444 !important; }

    .content-area { flex: 1; padding: 40px; overflow-y: auto; }
</style>

<div class="top-bar">
    <div class="system-title">
        <div class="system-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>
            </svg>
        </div>
        FK Student Club & Event Management - Admin Panel
    </div>

    <div class="user-profile-section">
        <div class="profile-group">
            <div class="top-bar-photo">
                <?php if (!empty($photo_path)): ?>
                    <img src="../<?php echo htmlspecialchars($photo_path); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="welcome-text">Admin: <?php echo isset($admin_name) ? $admin_name : $_SESSION['user_username']; ?></div>
        </div>
        <div class="role-badge">Administrator</div>
    </div>
</div>

<div class="main-layout">
    <div class="sidebar">
        <div class="sidebar-title">Admin Controls</div>
        <a href="administrator_dashboard.php" class="<?= ($current_page == 'administrator_dashboard.php') ? 'active' : '' ?>">Dashboard Overview</a>
        <a href="admin_profile.php" class="<?= ($current_page == 'admin_profile.php') ? 'active' : '' ?>">My Profile</a>
        <a href="manage_users.php" class="<?= ($current_page == 'manage_users.php') ? 'active' : '' ?>">Manage Users</a>
        <a href="manage_clubs.php" class="<?= ($current_page == 'manage_clubs.php') ? 'active' : '' ?>">Manage Clubs</a>
        <a href="manage_committees.php" class="<?= ($current_page == 'manage_committees.php') ? 'active' : '' ?>">Manage Committees</a>
        <a href="club_dashboard.php" class="<?= ($current_page == 'club_dashboard.php') ? 'active' : '' ?>">Club Analytics</a>
        <a href="club_directory.php" class="<?= ($current_page == 'club_directory.php') ? 'active' : '' ?>">Club Directory</a>
        <a href="manage_events.php" class="<?= ($current_page == 'manage_events.php') ? 'active' : '' ?>">Manage Events</a>
        <a href="system_reports.php" class="<?= ($current_page == 'system_report.php') ? 'active' : '' ?>">Reports & Analytics</a>
        <a href="../logout.php" class="logout-btn">LogOut</a>
    </div>