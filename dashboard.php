<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

$photo_path = "";
$stu_name = $username; 

$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);
if ($result_profile && $row = mysqli_fetch_assoc($result_profile)) {
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
}

$total_points = 0;
$sql_points = "SELECT SUM(point_value) as points FROM points WHERE userID = '$userID'";
$result_points = mysqli_query($link, $sql_points);
if ($result_points && $row = mysqli_fetch_assoc($result_points)) {
    $total_points = $row['points'] ? $row['points'] : 0;
}

$max_points = 1000;
$percentage = min(100, ($total_points / $max_points) * 100); 
$next_tier_points = 0;
$current_tier = "";

if ($total_points >= 1000) {
    $current_tier = "Outstanding";
    $next_tier_points = 0;
} elseif ($total_points >= 600) {
    $current_tier = "Advanced";
    $next_tier_points = 1000 - $total_points;
} elseif ($total_points >= 300) {
    $current_tier = "Intermediate";
    $next_tier_points = 600 - $total_points;
} else {
    $current_tier = "Beginner";
    $next_tier_points = 300 - $total_points;
}

$next_event = null;
$days_away_text = "";

$sql_next = "
    SELECT e.event_title, e.event_date, e.event_time 
    FROM eventregistration er
    JOIN events e ON er.eventID = e.eventID
    WHERE er.userID = '$userID' AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC 
    LIMIT 1
";
$result_next = mysqli_query($link, $sql_next);
if ($result_next && mysqli_num_rows($result_next) > 0) {
    $next_event = mysqli_fetch_assoc($result_next);
    
    $today = new DateTime('today');
    $eventDate = new DateTime($next_event['event_date']);
    $interval = $today->diff($eventDate);
    $days_diff = (int)$interval->format('%R%a'); 
    
    if ($days_diff == 0) {
        $days_away_text = "Happening Today!";
    } elseif ($days_diff == 1) {
        $days_away_text = "Tomorrow!";
    } else {
        $days_away_text = $days_diff . " Days Away";
    }
}

$clubs = [];
$sql_clubs = "
    SELECT c.club_name, mr.m_role_desc 
    FROM membership m
    JOIN club c ON m.clubID = c.clubID
    JOIN membershiprole mr ON m.roleID = mr.roleID
    WHERE m.userID = '$userID'
";
$result_clubs = mysqli_query($link, $sql_clubs);
while ($row = mysqli_fetch_assoc($result_clubs)) {
    $clubs[] = $row;
}
$total_clubs = count($clubs);

$events = [];
$sql_events = "
    SELECT e.event_title, e.event_date, e.event_venue, er.registration_status 
    FROM eventregistration er
    JOIN events e ON er.eventID = e.eventID
    WHERE er.userID = '$userID'
    ORDER BY e.event_date ASC
";
$result_events = mysqli_query($link, $sql_events);
while ($row = mysqli_fetch_assoc($result_events)) {
    $events[] = $row;
}
$total_events = count($events);

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - FK Management System</title>
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

        /* FEATURE CARDS */
        .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 40px; }
        
        .points-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 5px solid #10b981; }
        .points-card-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .points-title { font-size: 14px; color: #718096; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .points-number { font-size: 36px; font-weight: bold; color: #1a202c; line-height: 1; }
        .tier-badge { background-color: #e6ffed; color: #059669; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; border: 1px solid #a7f3d0; }
        
        .progress-container { background-color: #e2e8f0; border-radius: 10px; height: 12px; width: 100%; overflow: hidden; margin-bottom: 10px; }
        .progress-fill { background: linear-gradient(90deg, #10b981 0%, #34d399 100%); height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }
        .progress-text { font-size: 13px; color: #718096; font-weight: bold; }

        .next-event-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; }
        .next-event-card::after { content: ''; position: absolute; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%; top: -30px; right: -30px; }
        
        .alert-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: bold; margin-bottom: 10px; }
        .alert-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; color: white; z-index: 2; }
        .alert-time { font-size: 15px; color: #cbd5e1; margin-bottom: 15px; z-index: 2; }
        .alert-badge { display: inline-block; background-color: #ef4444; color: white; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: bold; width: fit-content; z-index: 2; }

        /* LIST CARDS */
        .details-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        .detail-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .detail-card h3 { font-size: 18px; color: #1a202c; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f4f8; }
        
        .item-list { list-style: none; }
        .item-list li { padding: 15px 0; border-bottom: 1px solid #f0f4f8; display: flex; justify-content: space-between; align-items: center; }
        .item-list li:last-child { border-bottom: none; }
        .item-info h4 { font-size: 15px; color: #2d3748; margin-bottom: 4px; }
        .item-info p { font-size: 13px; color: #718096; }
        .item-badge { background-color: #e6ffed; color: #10b981; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; }
        .empty-state { text-align: center; padding: 30px 0; color: #a0aec0; font-size: 14px; font-style: italic; }
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
            <a href="dashboard.php" class="active">Dashboard</a>       
            <a href="student_dashboard.php">My Profile</a>
			<a href="committee_details.php">My Committee Details</a> 
            <a href="club_directory.php">Club Directory</a>
            <a href="event_directory.php">Event Directory</a>
            <a href="participation.php">My Participation</a>
            <a href="logout.php" class="logout-btn">LogOut</a>
        </div>

        <div class="content-area">

            <div class="feature-grid">
                
                <div class="points-card">
                    <div class="points-card-header">
                        <div>
                            <div class="points-title">Total Merit Points</div>
                            <div class="points-number"><?php echo $total_points; ?></div>
                        </div>
                        <div class="tier-badge">Tier: <?php echo $current_tier; ?></div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                    
                    <div class="progress-text">
                        <?php if ($current_tier == "Outstanding"): ?>
                            You have reached the maximum tier!
                        <?php else: ?>
                            <?php echo $next_tier_points; ?> points away from the next tier
                        <?php endif; ?>
                    </div>
                </div>

                <div class="next-event-card">
                    <div class="alert-label">📅 What's Next?</div>
                    <?php if ($next_event): ?>
                        <div class="alert-title"><?php echo htmlspecialchars($next_event['event_title']); ?></div>
                        <div class="alert-time">
                            <?php 
                                echo date("l, F jS", strtotime($next_event['event_date'])); 
                                echo " at " . date("g:i A", strtotime($next_event['event_time'])); 
                            ?>
                        </div>
                        <div class="alert-badge"><?php echo $days_away_text; ?></div>
                    <?php else: ?>
                        <div class="alert-title" style="color: #94a3b8; font-size: 18px;">No upcoming events scheduled.</div>
                        <div class="alert-time">Take a break, or browse the directory to join something new!</div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="details-grid">
                
                <div class="detail-card">
                    <h3>My Registered Events</h3>
                    <?php if ($total_events > 0): ?>
                        <ul class="item-list">
                            <?php foreach ($events as $event): ?>
                            <li>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($event['event_title']); ?></h4>
                                    <p>Date: <?php echo htmlspecialchars($event['event_date']); ?> | Venue: <?php echo htmlspecialchars($event['event_venue']); ?></p>
                                </div>
                                <div class="item-badge"><?php echo htmlspecialchars($event['registration_status']); ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">You have not registered for any upcoming events yet.<br><a href="event_directory.php" style="color: #10b981; text-decoration: none; font-weight: bold; margin-top: 10px; display: inline-block;">Browse Events</a></div>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <h3>My Clubs</h3>
                    <?php if ($total_clubs > 0): ?>
                        <ul class="item-list">
                            <?php foreach ($clubs as $club): ?>
                            <li>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($club['club_name']); ?></h4>
                                    <p>Role: <?php echo htmlspecialchars($club['m_role_desc']); ?></p>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">You haven't joined any clubs yet.<br><a href="club_directory.php" style="color: #10b981; text-decoration: none; font-weight: bold; margin-top: 10px; display: inline-block;">Explore Clubs</a></div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>