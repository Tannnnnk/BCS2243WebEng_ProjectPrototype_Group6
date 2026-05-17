<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

$admin_name = $username; 
$photo_path = "";
try {
    $sql_admin = "SELECT admin_name, admin_photo FROM administrator WHERE userID = '$userID'";
    $res_admin = mysqli_query($link, $sql_admin);
    if ($res_admin && $row = mysqli_fetch_assoc($res_admin)) {
        $admin_name = !empty($row['admin_name']) ? $row['admin_name'] : $username;
        $photo_path = !empty($row['admin_photo']) ? $row['admin_photo'] : "";
    }
} catch (Exception $e) {}

$total_students = 0;
try {
    $sql_students = "SELECT COUNT(*) as count FROM users WHERE user_role = 'Student'";
    $res_students = mysqli_query($link, $sql_students);
    if ($res_students && $row = mysqli_fetch_assoc($res_students)) { $total_students = $row['count']; }
} catch (Exception $e) {}

$total_clubs = 0;
try {
    $sql_clubs = "SELECT COUNT(*) as count FROM club";
    $res_clubs = mysqli_query($link, $sql_clubs);
    if ($res_clubs && $row = mysqli_fetch_assoc($res_clubs)) { $total_clubs = $row['count']; }
} catch (Exception $e) {}

$total_events = 0;
try {
    $sql_events = "SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE()";
    $res_events = mysqli_query($link, $sql_events);
    if ($res_events && $row = mysqli_fetch_assoc($res_events)) { $total_events = $row['count']; }
} catch (Exception $e) {}

$recent_users = [];
try {
    $sql_recent = "SELECT userID, user_username, user_role FROM users ORDER BY userID DESC LIMIT 5";
    $res_recent = mysqli_query($link, $sql_recent);
    if ($res_recent) {
        while ($row = mysqli_fetch_assoc($res_recent)) {
            $uid = $row['userID'];
            $display_name = $row['user_username']; 
            
            if ($row['user_role'] == 'Student') {
                try {
                    $q = mysqli_query($link, "SELECT stu_name FROM students WHERE userID='$uid'");
                    if ($q && $r = mysqli_fetch_assoc($q)) { $display_name = !empty($r['stu_name']) ? $r['stu_name'] : $display_name; }
                } catch (Exception $e) {}
            } else {
                try {
                    $q = mysqli_query($link, "SELECT admin_name FROM administrator WHERE userID='$uid'");
                    if ($q && $r = mysqli_fetch_assoc($q)) { $display_name = !empty($r['admin_name']) ? $r['admin_name'] : $display_name; }
                } catch (Exception $e) {}
            }
            $row['display_name'] = $display_name;
            $recent_users[] = $row;
        }
    }
} catch (Exception $e) {}

$club_names = [];
$club_members = [];
try {
    $sql_chart1 = "SELECT c.club_name, COUNT(m.userID) as member_count 
                   FROM club c 
                   LEFT JOIN membership m ON c.clubID = m.clubID 
                   GROUP BY c.clubID LIMIT 5";
    $res_chart1 = mysqli_query($link, $sql_chart1);
    if ($res_chart1) {
        while ($row = mysqli_fetch_assoc($res_chart1)) {
            $club_names[] = $row['club_name'];
            $club_members[] = $row['member_count'];
        }
    }
} catch (Exception $e) {}

$admin_count = 0;
try {
    $sql_admins = "SELECT COUNT(*) as count FROM users WHERE user_role = 'Administrator'";
    $res_admins = mysqli_query($link, $sql_admins);
    if ($res_admins && $row = mysqli_fetch_assoc($res_admins)) { $admin_count = $row['count']; }
} catch (Exception $e) {}

$trend_months = [];
$trend_participations = [];
$trend_registrations = [];

for ($i = 5; $i >= 0; $i--) {
    $monthName = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $yearNum = date('Y', strtotime("-$i months"));
    
    $trend_months[] = $monthName;
    
    $part_count = 0;
    try {
        $sql_part = "SELECT COUNT(*) as count FROM eventregistration er 
                     JOIN events e ON er.eventID = e.eventID 
                     WHERE MONTH(e.event_date) = '$monthNum' AND YEAR(e.event_date) = '$yearNum'";
        $res_part = mysqli_query($link, $sql_part);
        if ($res_part && $row = mysqli_fetch_assoc($res_part)) { $part_count = $row['count']; }
    } catch (Exception $e) {}
    $trend_participations[] = $part_count;

    $reg_count = 0;
    try {
        $sql_reg = "SELECT COUNT(*) as count FROM users 
                    WHERE MONTH(created_at) = '$monthNum' AND YEAR(created_at) = '$yearNum'";
        $res_reg = mysqli_query($link, $sql_reg);
        if ($res_reg && $row = mysqli_fetch_assoc($res_reg)) { $reg_count = $row['count']; }
    } catch (Exception $e) {}
    $trend_registrations[] = $reg_count;
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - FK Management System</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        .welcome-header { margin-bottom: 30px; }
        .welcome-header h2 { font-size: 28px; color: #1a202c; margin-bottom: 5px; }
        .welcome-header p { color: #718096; font-size: 16px; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: white; border-radius: 12px; padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 5px solid #0f172a;
        }
        .stat-card.green { border-color: #10b981; }
        .stat-card.blue { border-color: #3b82f6; }
        .stat-card h3 { font-size: 13px; color: #718096; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #1a202c; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px;}
        .chart-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .chart-header { font-size: 16px; font-weight: bold; color: #1a202c; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f4f8; }
        
        .bottom-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }

        .item-list { list-style: none; }
        .item-list li { padding: 12px 0; border-bottom: 1px solid #f0f4f8; display: flex; justify-content: space-between; align-items: center; }
        .item-list li:last-child { border-bottom: none; }
        .item-info h4 { font-size: 14px; color: #2d3748; margin-bottom: 2px; }
        .item-badge { background-color: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .item-badge.admin { background-color: #e0e7ff; color: #047857; }
        
        .canvas-container { position: relative; width: 100%; height: 250px; }
    </style>
</head>
<body>

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
                        <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="welcome-text">Admin: <?php echo htmlspecialchars($admin_name); ?></div>
            </div>
            <div class="role-badge">Administrator</div>
        </div>
    </div>

    <div class="main-layout">
        
        <div class="sidebar">
            <div class="sidebar-title">Admin Controls</div>
            <a href="administrator_dashboard.php" class="active">Dashboard Overview</a>
            <a href="admin_profile.php">My Profile</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_clubs.php">Manage Clubs</a>
            <a href="manage_events.php">Manage Events</a>
            <a href="system_reports.php">Reports & Analytics</a>
            <a href="logout.php" class="logout-btn">LogOut</a>
        </div>

        <div class="content-area">
            
            <div class="welcome-header">
                <h2>System Overview</h2>
                <p>Monitor platform usage, club activity, and registration metrics.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card blue">
                    <h3>Active Clubs</h3>
                    <div class="number"><?php echo $total_clubs; ?></div>
                </div>
                <div class="stat-card green">
                    <h3>Upcoming Events</h3>
                    <div class="number"><?php echo $total_events; ?></div>
                </div>
            </div>

            <div class="dashboard-grid">
                
                <div class="chart-card">
                    <div class="chart-header">Club Popularity (Members per Club)</div>
                    <div class="canvas-container">
                        <canvas id="clubBarChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">Recent Registrations</div>
                    <?php if (count($recent_users) > 0): ?>
                        <ul class="item-list">
                            <?php foreach ($recent_users as $user): ?>
                            <li>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($user['display_name']); ?></h4>
                                </div>
                                <div class="item-badge <?php echo $user['user_role'] === 'Administrator' ? 'admin' : ''; ?>">
                                    <?php echo htmlspecialchars($user['user_role']); ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: #718096; font-size: 14px;">No recent users found.</p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="bottom-grid">
                
                <div class="chart-card">
                    <div class="chart-header">User Role Breakdown</div>
                    <div class="canvas-container">
                        <canvas id="rolePieChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">Participation & Usage Trends (6 Months)</div>
                    <div class="canvas-container">
                        <canvas id="trendLineChart"></canvas>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            const clubNames = <?php echo empty($club_names) ? '[]' : json_encode($club_names); ?>;
            const clubMembers = <?php echo empty($club_members) ? '[]' : json_encode($club_members); ?>;
            const barCanvas = document.getElementById('clubBarChart');
            if(barCanvas && clubNames.length > 0) {
                new Chart(barCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: clubNames,
                        datasets: [{
                            label: 'Total Members',
                            data: clubMembers,
                            backgroundColor: '#3b82f6',
                            borderRadius: 4
                        }]
                    },
                    options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            }

            const pieCanvas = document.getElementById('rolePieChart');
            if(pieCanvas) {
                new Chart(pieCanvas.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: ['Students', 'Administrators'],
                        datasets: [{
                            data: [<?php echo $total_students; ?>, <?php echo $admin_count; ?>],
                            backgroundColor: ['#10b981', '#0f172a']
                        }]
                    },
                    options: { maintainAspectRatio: false }
                });
            }
            
            const trendMonths = <?php echo json_encode($trend_months); ?>;
            const trendParticipations = <?php echo json_encode($trend_participations); ?>;
            const trendRegistrations = <?php echo json_encode($trend_registrations); ?>;
            
            const lineCanvas = document.getElementById('trendLineChart');
            if(lineCanvas) {
                new Chart(lineCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trendMonths,
                        datasets: [
                            {
                                label: 'Event Participations',
                                data: trendParticipations,
                                borderColor: '#10b981', 
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4, 
                                fill: true
                            },
                            {
                                label: 'New User Registrations',
                                data: trendRegistrations,
                                borderColor: '#3b82f6', 
                                backgroundColor: 'transparent',
                                tension: 0.4,
                                borderDash: [5, 5] 
                            }
                        ]
                    },
                    options: { 
                        maintainAspectRatio: false, 
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        interaction: { mode: 'index', intersect: false }
                    }
                });
            }
            
        });
    </script>
</body>
</html>