<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

// Fetch basic student info for top bar
$stu_name = $username; 
$photo_path = ""; 

$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);
if ($result_profile && mysqli_num_rows($result_profile) > 0) {
    $row = mysqli_fetch_assoc($result_profile);
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
}

// 1. Fetch Membership Details
$memberships = [];
$sql_mem = "
    SELECT m.memberID, c.club_name, mr.m_role_desc, m.start_date, m.end_date 
    FROM membership m 
    JOIN club c ON m.clubID = c.clubID 
    JOIN membershiprole mr ON m.roleID = mr.roleID 
    WHERE m.userID = '$userID'
";
$result_mem = mysqli_query($link, $sql_mem);
if ($result_mem) {
    while ($row = mysqli_fetch_assoc($result_mem)) {
        $memberships[] = $row;
    }
}

// 2. Fetch Handled Events (Committee Details)
$committees = [];
$sql_com = "
    SELECT com.committeeID, e.event_title 
    FROM committee com 
    JOIN membership m ON com.memberID = m.memberID 
    JOIN events e ON com.eventID = e.eventID 
    WHERE m.userID = '$userID'
";
$result_com = mysqli_query($link, $sql_com);
if ($result_com) {
    while ($row = mysqli_fetch_assoc($result_com)) {
        $committees[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Details - FK Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { display: flex; flex-direction: column; height: 100vh; background-color: #f4f7f6; color: #333; }
        
        .top-bar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;
            padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10;
        }

        .system-title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; display: flex; align-items: center; gap: 12px; }
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

        .main-layout { display: flex; flex: 1; overflow: hidden; }
        
        .sidebar { width: 250px; background-color: white; border-right: 1px solid #e2e8f0; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 1px; }
        .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; }
        .sidebar a:hover { background-color: #f8fafc; color: #10b981; }
        .sidebar a.active { background-color: #e6ffed; color: #10b981; border-left: 4px solid #10b981; }
        .logout-btn { margin-top: auto; border-top: 1px solid #e2e8f0; }

        .content-area { flex: 1; padding: 40px; overflow-y: auto; }
        
        .header-section { margin-bottom: 30px; }
        .header-section h2 { font-size: 28px; color: #1a202c; margin-bottom: 5px; }
        .header-section p { color: #718096; font-size: 15px; }

        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 40px; border-top: 4px solid #10b981; }
        .table-title { padding: 20px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 18px; font-weight: bold; color: #2d3748; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #4a5568; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 16px 20px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 20px; border-bottom: 1px solid #e2e8f0; color: #1a202c; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: #f1f5f9; }

        .role-tag { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; background-color: #e0e7ff; color: #3730a3; }
        .event-tag { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; background-color: #fef08a; color: #9a3412; }
    </style>
</head>
<body>

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
            <div class="role-badge">Role: <?php echo htmlspecialchars($role); ?></div>
        </div>
    </div>

    <div class="main-layout">
        
        <div class="sidebar">
            <div class="sidebar-title">Main Menu</div>
            <a href="dashboard.php">Dashboard</a>
            <a href="student_dashboard.php">My Profile</a>
            <a href="committee_details.php" class="active">My Committee Details</a>        
            <a href="club_directory.php">Club Directory</a>
            <a href="event_directory.php">Event Directory</a>
            <a href="participation.php">My Participation</a>
            <a href="logout.php" class="logout-btn">LogOut</a>
        </div>

        <div class="content-area">

            <div class="header-section">
                <h2>My Committee Details</h2>
                <p>View your official club memberships and the upcoming events you are managing.</p>
            </div>

            <div class="table-card">
                <div class="table-title">1. Club Memberships</div>
                <table>
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Club Name</th>
                            <th>Club Role</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($memberships) > 0): ?>
                            <?php foreach ($memberships as $mem): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($mem['memberID']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($mem['club_name']); ?></td>
                                    <td><span class="role-tag"><?php echo htmlspecialchars($mem['m_role_desc']); ?></span></td>
                                    <td style="color: #718096;"><?php echo htmlspecialchars($mem['start_date']); ?></td>
                                    <td style="color: #718096;"><?php echo htmlspecialchars($mem['end_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px; color: #718096;">You are not currently a member of any clubs.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card" style="border-top-color: #f59e0b;">
                <div class="table-title">2. Events Handled</div>
                <table>
                    <thead>
                        <tr>
                            <th>Committee ID</th>
                            <th>Assigned Event Title</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($committees) > 0): ?>
                            <?php foreach ($committees as $com): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($com['committeeID']); ?></strong></td>
                                    <td><span class="event-tag"><?php echo htmlspecialchars($com['event_title']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; padding: 30px; color: #718096;">You have not been assigned to handle any specific events.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>
</html>