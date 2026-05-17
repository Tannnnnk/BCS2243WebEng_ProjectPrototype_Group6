<?php
require_once '../db_connection.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
    body { display: flex; flex-direction: column; height: 100vh; background-color: #f4f7f6; color: #333; }
        
    /* TOP NAVIGATION */
    .top-bar {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;
        padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10;
    }
        
    .system-title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; display: flex; align-items: center; gap: 12px; }
        
    /* NEW: System Logo Styling */
    .system-logo {
        width: 36px; height: 36px; background-color: white; color: #10b981;
        border-radius: 8px; display: flex; justify-content: center; align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
        
    .user-profile-section { display: flex; align-items: center; gap: 20px; }
    
    .profile-group { display: flex; align-items: center; gap: 12px; }
    .top-bar-photo {
        width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); 
        border-radius: 50%; border: 2px solid white;
        display: flex; justify-content: center; align-items: center; 
        overflow: hidden; font-size: 14px; font-weight: bold;
    }
    .welcome-text { font-size: 15px; font-weight: bold; }
        
    .role-badge { background-color: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; }

    /* SIDEBAR & LAYOUT */
    .main-layout { display: flex; flex: 1; overflow: hidden; }
    
    .sidebar { width: 250px; background-color: white; border-right: 1px solid #e2e8f0; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
    .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 1px; }
    .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; }
    .sidebar a:hover { background-color: #f8fafc; color: #10b981; }
    .sidebar a.active { background-color: #e6ffed; color: #10b981; border-left: 4px solid #10b981; }
    .logout-btn { margin-top: auto; border-top: 1px solid #e2e8f0; }

    .content-area { flex: 1; padding: 40px; overflow-y: auto; }
</style>

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
                    <img src="../<?php echo htmlspecialchars($photo_path); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?php echo strtoupper(substr($stu_name, 0, 1)); ?>
                <?php endif; ?>
             </div>
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($stu_name); ?></div>
        </div>
        <div class="role-badge">Role: <?php echo htmlspecialchars($role); ?></div>
    </div>
</div>

<div class="main-layout">
    <div class="sidebar">
        <div class="sidebar-title">Main Menu</div>
        <a href="student_dashboard.php" class="<?= ($current_page == 'student_dashboard.php') ? 'active' : '' ?>">Dashboard</a>
        <a href="profile_dashboard.php" class="<?= ($current_page == 'profile_dashboard.php') ? 'active' : '' ?>">My Profile</a>
        <a href="committee_details.php" class="<?= ($current_page == 'committee_details.php') ? 'active' : '' ?>">My Committee Details</a>        
        <a href="club_directory.php" class="<?= ($current_page == 'club_directory.php') ? 'active' : '' ?>">Club Directory</a>
        <a href="event_directory.php" class="<?= ($current_page == 'event_directory.php') ? 'active' : '' ?>">Event Directory</a>
        <a href="participation.php" class="<?= ($current_page == 'participation.php') ? 'active' : '' ?>">My Participation</a>
        <a href="../logout.php" class="logout-btn">LogOut</a>
    </div>