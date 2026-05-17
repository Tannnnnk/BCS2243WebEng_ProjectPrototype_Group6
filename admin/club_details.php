<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$role   = $_SESSION['user_role'];
$username = $_SESSION['user_username'];
$display_name = $username;
$photo_path   = "";

if ($role == 'Student' || $role == 'Committee') {
    $res = mysqli_query($link, "SELECT stu_name, stu_profile_photo FROM students WHERE userID='$userID'");
    if ($res && $r = mysqli_fetch_assoc($res)) {
        $display_name = $r['stu_name'] ?: $username;
        $photo_path   = $r['stu_profile_photo'] ?: "";
    }
} else {
    $res = mysqli_query($link, "SELECT admin_name, admin_photo FROM administrator WHERE userID='$userID'");
    if ($res && $r = mysqli_fetch_assoc($res)) {
        $display_name = $r['admin_name'] ?: $username;
        $photo_path   = $r['admin_photo'] ?: "";
    }
}
if (!isset($_GET['clubID'])) {
    header("Location: club_directory.php");
    exit();
}

$clubID = mysqli_real_escape_string($link, $_GET['clubID']);

// 1. Fetch club details and join for Advisor name (ERD COMPLIANT)
$club = null;
$sql = "SELECT c.*, COALESCE(a.admin_name, s.stu_name, 'No Advisor') AS club_advisor_name
        FROM club c
        LEFT JOIN administrator a ON c.userID = a.userID
        LEFT JOIN students s ON c.userID = s.userID
        WHERE c.clubID = '$clubID'";

$res = mysqli_query($link, $sql);
if ($res && $r = mysqli_fetch_assoc($res)) { 
    $club = $r; 
}

if (!$club) { 
    header("Location: club_directory.php"); 
    exit(); 
}

// 2. Fetch committee from membership table
$committee = [];
$res_comm = mysqli_query($link, "
    SELECT mr.m_role_desc as comm_position, s.stu_name, s.stu_ID
    FROM membership m
    JOIN membershiprole mr ON m.roleID = mr.roleID
    JOIN students s ON m.userID = s.userID
    WHERE m.clubID = '$clubID' 
    AND mr.m_role_desc IN ('President', 'Vice President', 'Secretary', 'Treasurer')
    ORDER BY FIELD(mr.m_role_desc, 'President', 'Vice President', 'Secretary', 'Treasurer')
");
if ($res_comm) { 
    while ($r = mysqli_fetch_assoc($res_comm)) { $committee[] = $r; } 
}

// 3. SAFE EVENT FETCH (Crash-Proof logic for Teammate's table)
$events = [];
$col_to_use = null;

// Check table structure first to avoid "Unknown Column" Fatal Error
$structure_query = mysqli_query($link, "SHOW COLUMNS FROM events");
if ($structure_query) {
    while ($column = mysqli_fetch_assoc($structure_query)) {
        if ($column['Field'] == 'clubID') {
            $col_to_use = 'clubID';
            break;
        } elseif ($column['Field'] == 'event_clubID') {
            $col_to_use = 'event_clubID';
            break;
        }
    }
}

// Only query if a valid linking column exists
if ($col_to_use) {
    $res_events = mysqli_query($link, "SELECT * FROM events WHERE $col_to_use = '$clubID' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
    if ($res_events) { 
        while ($r = mysqli_fetch_assoc($res_events)) { $events[] = $r; } 
    }
}

// 4. Member count
$member_count = 0;
$res_m = mysqli_query($link, "SELECT COUNT(*) as c FROM membership WHERE clubID='$clubID'");
if ($res_m && $rm = mysqli_fetch_assoc($res_m)) $member_count = $rm['c'];

// 5. User Display Info
$display_name = $username;
$photo_path = "";
if ($role == 'Student' || $role == 'Committee') {
    $res = mysqli_query($link, "SELECT stu_name, stu_profile_photo FROM students WHERE userID='$userID'");
    if ($res && $r = mysqli_fetch_assoc($res)) { 
        $display_name = $r['stu_name']; 
        $photo_path = $r['stu_profile_photo']; 
    }
} elseif ($role == 'Administrator') {
    $res = mysqli_query($link, "SELECT admin_name, admin_photo FROM administrator WHERE userID='$userID'");
    if ($res && $r = mysqli_fetch_assoc($res)) { 
        $display_name = $r['admin_name']; 
        $photo_path = $r['admin_photo']; 
    }
}

$is_admin = ($role == 'Administrator');
$admin_name = $display_name; 

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($club['club_name']); ?> - FK Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { display: flex; flex-direction: column; height: 100vh; background-color: #f4f7f6; }

        .top-bar { background: linear-gradient(135deg, <?php echo $is_admin ? '#0f172a 0%, #1e293b' : '#10b981 0%, #059669'; ?> 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .system-title { font-size: 22px; font-weight: bold; display: flex; align-items: center; gap: 12px; }
        .system-logo { width: 36px; height: 36px; background-color: <?php echo $is_admin?'#10b981':'white'; ?>; color: <?php echo $is_admin?'white':'#10b981'; ?>; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        .user-profile-section { display: flex; align-items: center; gap: 20px; }
        .profile-group { display: flex; align-items: center; gap: 12px; }
        .top-bar-photo { width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); border-radius: 50%; border: 2px solid white; display: flex; justify-content: center; align-items: center; overflow: hidden; font-size: 14px; font-weight: bold; }
        .welcome-text { font-size: 15px; font-weight: bold; }
        .role-badge { background-color: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; }

        .main-layout { display: flex; flex: 1; overflow: hidden; }
        .sidebar { width: 250px; background: white; border-right: 1px solid #e2e8f0; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 1px; }
        .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; display: block; }
        .sidebar a:hover { background-color: #f8fafc; }
        .sidebar a.active { background-color: #f1f5f9; border-left: 4px solid #10b981; color: #10b981; }
        .logout-btn { margin-top: auto; border-top: 1px solid #e2e8f0; color: #ef4444 !important; }

        .content-area { flex: 1; padding: 40px; overflow-y: auto; }

        .back-link { color: #10b981; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 20px; }

        .club-header { background: white; border-radius: 12px; padding: 35px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; border-top: 5px solid #10b981; display: flex; justify-content: space-between; align-items: flex-start; }
        .club-header-left h1 { font-size: 28px; color: #1a202c; margin-bottom: 8px; }
        .club-header-left p { color: #718096; font-size: 15px; line-height: 1.6; max-width: 600px; }
        .club-header-meta { margin-top: 15px; display: flex; gap: 25px; }
        .meta-item { font-size: 14px; color: #4a5568; }
        .meta-item strong { color: #1a202c; }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .section-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .section-title { font-size: 16px; font-weight: bold; color: #1a202c; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f4f8; }

        .committee-list { list-style: none; }
        .committee-list li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f4f8; font-size: 14px; }
        .committee-list li:last-child { border-bottom: none; }
        .position-tag { background-color: #e0e7ff; color: #3730a3; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }

        .event-list { list-style: none; }
        .event-list li { padding: 12px 0; border-bottom: 1px solid #f0f4f8; }
        .event-list li:last-child { border-bottom: none; }
        .event-title { font-size: 14px; font-weight: bold; color: #1a202c; }
        .event-meta { font-size: 12px; color: #718096; margin-top: 3px; }

        .empty-msg { color: #a0aec0; font-size: 14px; font-style: italic; padding: 10px 0; }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="system-title">
        <div class="system-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        </div>
        FK Student Club & Event Management
    </div>
    <div class="user-profile-section">
        <div class="profile-group">
            <div class="top-bar-photo">
    <?php 
    // Check if path exists and prepending ../ if the photo is stored in a root directory
    if (!empty($photo_path)): 
        // If the path doesn't already start with http or ../, add ../
        $final_path = (strpos($photo_path, 'http') === 0) ? $photo_path : '../' . $photo_path;//one dir up 
    ?>
        <img src="<?php echo htmlspecialchars($final_path); ?>" 
             style="width:100%;height:100%;object-fit:cover;" 
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <span style="display:none;"><?php echo strtoupper(substr($display_name, 0, 1)); ?></span>
    <?php else: ?>
        <?php echo strtoupper(substr($display_name, 0, 1)); ?>
    <?php endif; ?>
</div>
            <div class="welcome-text">Welcome, <?php echo htmlspecialchars($display_name); ?></div>
        </div>
        <div class="role-badge"><?php echo htmlspecialchars($role); ?></div>
    </div>
</div>

<div class="main-layout">
    <div class="sidebar">
        <?php if ($is_admin): ?>
            <div class="sidebar-title">Admin Controls</div>
            <a href="administrator_dashboard.php">Dashboard Overview</a>
            <a href="admin_profile.php">My Profile</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_clubs.php">Manage Clubs</a>
            <a href="manage_committees.php">Manage Committees</a>
            <a href="club_dashboard.php">Club Dashboard</a>
            <a href="club_directory.php" class="active">Club Directory</a>
            <a href="manage_events.php">Manage Events</a>
            <a href="system_reports.php">Reports & Analytics</a>
        <?php else: ?>
            <div class="sidebar-title">Main Menu</div>
            <a href="dashboard.php">Dashboard</a>
            <a href="student_dashboard.php">My Profile</a>
            <a href="committee_details.php">My Committee Details</a>
            <a href="club_directory.php" class="active">Club Directory</a>
            <a href="event_directory.php">Event Directory</a>
            <a href="participation.php">My Participation</a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn">LogOut</a>
    </div>

    <div class="content-area">

        <a href="club_directory.php" class="back-link">← Back to Club Directory</a>

        <!-- CLUB HEADER -->
        <div class="club-header">
            <div class="club-header-left">
                <h1><?php echo htmlspecialchars($club['club_name']); ?></h1>
                <p><?php echo htmlspecialchars($club['club_desc']); ?></p>
                <div class="club-header-meta">
                    <div class="meta-item">Advisor: <strong><?php echo htmlspecialchars($club['club_advisor_name']); ?></strong></div>
                    <div class="meta-item">Members: <strong><?php echo $member_count; ?></strong></div>
                    <div class="meta-item">Committee: <strong><?php echo count($committee); ?></strong></div>
                </div>
            </div>
            <span class="status-badge <?php echo $club['club_operational_status']=='Active'?'status-active':'status-inactive'; ?>">
                <?php echo htmlspecialchars($club['club_operational_status']); ?>
            </span>
        </div>

        <!-- COMMITTEE + EVENTS -->
        <div class="two-col">
            <div class="section-card">
                <div class="section-title">Committee Members</div>
                <?php if (count($committee) > 0): ?>
                    <ul class="committee-list">
                        <?php foreach ($committee as $cm): ?>
                            <li>
                                <span><?php echo htmlspecialchars($cm['stu_name']); ?> <span style="color:#718096;font-size:12px;">(<?php echo htmlspecialchars($cm['stu_ID']); ?>)</span></span>
                                <span class="position-tag"><?php echo htmlspecialchars($cm['comm_position']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-msg">No committee members assigned yet.</p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-title">Upcoming Events</div>
                <?php if (count($events) > 0): ?>
                    <ul class="event-list">
                        <?php foreach ($events as $ev): ?>
                            <li>
                                <div class="event-title"><?php echo htmlspecialchars($ev['event_title']); ?></div>
                                <div class="event-meta">
                                    <?php echo date('d M Y', strtotime($ev['event_date'])); ?> |
                                    <?php echo htmlspecialchars($ev['event_venue']); ?> |
                                    Max: <?php echo $ev['event_max_participants']; ?> pax
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-msg">No upcoming events for this club.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>