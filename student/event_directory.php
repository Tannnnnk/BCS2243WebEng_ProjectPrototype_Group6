<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Guard Check: Secure the context to authenticate Student users
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

// --- DB QUERY: FETCH CURRENT STUDENT INFORMATION & PROFILE PICTURE ---
$photo_path = "";
$stu_name = $username; 

$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);

if ($result_profile && $row = mysqli_fetch_assoc($result_profile)) {
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
}

$target_file = __DIR__ . '/../uploads/' . $photo_path;

if (!empty($photo_path) && file_exists($target_file)) {
    $img_src = "../uploads/" . htmlspecialchars($photo_path);
} else {
    $img_src = "../images/default-avatar.png"; 
}

$username = '<img src="' . $img_src . '" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; display: inline-block; vertical-align: middle; margin-right: 12px; border: 2px solid #ffffff;">' . htmlspecialchars($stu_name);

// --- 2. PRIVILEGE CHECK: CHECK IF COMMITTEE IN ANY CLUB (roleID < 'R08') ---
$check_committee = "SELECT m.*, mr.m_role_desc FROM membership m 
                    JOIN membershiprole mr ON m.roleID = mr.roleID 
                    WHERE m.userID = '$userID' AND m.roleID < 'R08' LIMIT 1";
$res_committee = mysqli_query($link, $check_committee);
$is_committee = (mysqli_num_rows($res_committee) > 0);

$committee_role = "General Student";
if ($is_committee) {
    $com_row = mysqli_fetch_assoc($res_committee);
    $committee_role = $com_row['m_role_desc'];
}

$msg = "";
$msg_type = "";

// --- FETCH CLUB ID FROM URL OR FALLBACK TO CLUB TABLE ---
$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';

if (empty($clubID)) {
    // Pull explicitly from the 'club' table 
    $fallback_query = "SELECT clubID FROM club LIMIT 1";
    $fallback_res = mysqli_query($link, $fallback_query);
    if ($fallback_res && $fallback_row = mysqli_fetch_assoc($fallback_res)) {
        $clubID = $fallback_row['clubID'];
    }
}

// --- 3. ACTION PROCESSOR: STUDENT SELF-REGISTRATION ---
if (isset($_POST['register_event'])) {
    $eventID = mysqli_real_escape_string($link, $_POST['eventID']);
    $today = date('Y-m-d');
    
    $check_dup = mysqli_query($link, "SELECT * FROM eventregistration WHERE userID = '$userID' AND eventID = '$eventID'");
    if (mysqli_num_rows($check_dup) > 0) {
        $msg = "⚠️ You are already registered for this event.";
        $msg_type = "error";
    } else {
        $ins = "INSERT INTO eventregistration (userID, eventID, registration_date, registration_status) VALUES ('$userID', '$eventID', '$today', 'Confirmed')";
        if (mysqli_query($link, $ins)) {
            $msg = "🎉 Successfully registered for the event!";
            $msg_type = "success";
        } else {
            $msg = "❌ Error processing registration entry.";
            $msg_type = "error";
        }
    }
}

// --- 4. ACTION PROCESSOR: COMMITTEE DIRECT TRACK REMOVAL ---
if (isset($_GET['delete_id']) && $is_committee) {
    $del_id = mysqli_real_escape_string($link, $_GET['delete_id']);
    if (mysqli_query($link, "DELETE FROM events WHERE eventID = '$del_id'")) {
        $msg = "✅ Event successfully removed.";
        $msg_type = "success";
    } else {
        $msg = "❌ Error deleting event.";
        $msg_type = "error";
    }
}

// --- 5. COMPILING STATISTICAL METRICS ---
$total_events = 0;
$res_count = mysqli_query($link, "SELECT COUNT(*) as total FROM events");
if ($res_count) { $total_events = mysqli_fetch_assoc($res_count)['total']; }

$total_pax = 0;
$res_pax = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration");
if ($res_pax) { $total_pax = mysqli_fetch_assoc($res_pax)['total']; }

$my_registrations_count = 0;
$res_my_reg = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration WHERE userID = '$userID'");
if ($res_my_reg) { $my_registrations_count = mysqli_fetch_assoc($res_my_reg)['total']; }

$active_upcoming = 0;
$res_upcoming = mysqli_query($link, "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()");
if ($res_upcoming) { $active_upcoming = mysqli_fetch_assoc($res_upcoming)['total']; }

// Fetch user merit points total
$total_points = 0;
$res_points = mysqli_query($link, "SELECT SUM(point_value) as points FROM points WHERE userID = '$userID'");
if ($res_points && $row = mysqli_fetch_assoc($res_points)) {
    $total_points = $row['points'] ? $row['points'] : 0;
}

// Global dataset execution
$query_all = "SELECT * FROM events ORDER BY event_date ASC";
$result_events = mysqli_query($link, $query_all);

$query_rec = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
$recommended_events = mysqli_query($link, $query_rec);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Directory Portal</title>
    <style>
        .workspace-wrapper { display: flex; flex-direction: column; gap: 25px; width: 100%; }
        .alert { padding: 12px 20px; border-radius: 6px; font-weight: 500; font-size: 14px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .actions-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .actions-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .action-card { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; text-align: center; border-radius: 8px; text-decoration: none; color: #475569; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .action-card:hover { background: #ecfdf5; border-color: #10b981; color: #065f46; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f8fafc; padding: 12px; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; display: inline-block; }
        .btn-register { background-color: #3b82f6; color: white; }
        .btn-register:hover { background-color: #2563eb; }
        .btn-edit { background-color: #f59e0b; color: white; margin-right: 5px; }
        .btn-delete { background-color: #ef4444; color: white; }
        .role-indicator { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; }
        .ind-com { background-color: #fef3c7; color: #d97706; }
        .ind-stu { background-color: #e0f2fe; color: #0369a1; }
        .footer-split { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 5px; }
        .split-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .split-card h3 { font-size: 14px; color: #64748b; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px; }
        .stat-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #475569; }
        .stat-item:last-child { border-bottom: none; }
        .stat-item span { font-weight: bold; color: #1e293b; font-size: 16px; }
        .chart-placeholder { background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 8px; height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #94a3b8; font-size: 13px; text-align: center; gap: 5px; margin-top: 10px; }
        .rec-item { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .rec-item:last-child { border-bottom: none; }
        .rec-title { font-weight: bold; color: #1e293b; font-size: 14px; }
        .rec-meta { font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="workspace-wrapper">
            
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if ($is_committee): ?>
                <div class="central-board">
                    <div class="board-title">
                        <span>🛠️ Member Dashboard (Committee Console)</span>
                        <div>
                            <span class="role-indicator ind-com">Viewing as: <?php echo htmlspecialchars($committee_role); ?></span>
                        </div>
                    </div>

                    <div class="actions-grid-4">
                        <a href="create_event.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">➕ Create Events</a>
                        <a href="manage_events.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">📝 Manage Event</a>
                        <a href="manage_attendance.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">📋 Event Participant List</a>
                        <a href="report_page.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">📊 Report</a> 
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Event Name Title</th>
                                <th>Venue Location</th>
                                <th>Scheduled Date</th>
                                <th>Action Controls</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_events && mysqli_num_rows($result_events) > 0): ?>
                                <?php while ($event = mysqli_fetch_assoc($result_events)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></td>
                                        <td>📍 <?php echo htmlspecialchars($event['event_venue']); ?></td>
                                        <td>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                                        <td>
                                            <a href="manage_events.php?edit_id=<?php echo $event['eventID']; ?>&clubID=<?php echo urlencode($clubID); ?>" class="btn btn-edit">Edit</a>
                                            <a href="event_directory.php?delete_id=<?php echo $event['eventID']; ?>&clubID=<?php echo urlencode($clubID); ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to permanently delete this event track record?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; color:#94a3b8;">No events listed in the database system.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="footer-split">
                    <div class="split-card">
                        <h3>📊 Summary</h3>
                        <div class="stat-item">Total Event <span><?php echo $total_events; ?></span></div>
                        <div class="stat-item">Upcoming Event <span><?php echo $active_upcoming; ?></span></div>
                        <div class="stat-item">Total Participants <span><?php echo $total_pax; ?></span></div>
                    </div>
                    <div class="split-card">
                        <h3>📈 Chart Overview</h3>
                        <div class="chart-placeholder">
                            <strong>Event by Category | Monthly Event Trend</strong>
                            <span style="font-size:11px; color:#94a3b8;">PopularEvent by Registration</span>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="central-board">
                    <div class="board-title">
                        <span>📅 Student Dashboard Portal</span>
                        <span class="role-indicator ind-stu">Viewing as: Student</span>
                    </div>

                    <div class="actions-grid-3">
                        <a href="#browse" class="action-card">🔍 Browse Events</a>
                        <a href="participation.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">📌 My Registration</a>
                        <a href="participation.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">⏳ Event History</a>
                    </div>

                    <div id="browse">
                        <table>
                            <thead>
                                <tr>
                                    <th>Available Club Events</th>
                                    <th>Venue Location</th>
                                    <th>Scheduled Date</th>
                                    <th>Action Controls</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_events && mysqli_num_rows($result_events) > 0): ?>
                                    <?php mysqli_data_seek($result_events, 0); // Reset pointer ?>
                                    <?php while ($event = mysqli_fetch_assoc($result_events)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></td>
                                            <td>📍 <?php echo htmlspecialchars($event['event_venue']); ?></td>
                                            <td>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                                            <td>
                                                <form method="POST" action="event_directory.php?clubID=<?php echo urlencode($clubID); ?>" style="display:inline;">
                                                    <input type="hidden" name="eventID" value="<?php echo htmlspecialchars($event['eventID']); ?>">
                                                    <button type="submit" name="register_event" class="btn btn-register">Register for Event</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center; color:#94a3b8;">No open events available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="footer-split">
                    <div class="split-card">
                        <h3>📊 Summary Section</h3>
                        <div class="stat-item">Registered Events <span><?php echo $my_registrations_count; ?></span></div>
                        <div class="stat-item">Upcoming Events Available <span><?php echo $active_upcoming; ?></span></div>
                        <div class="stat-item">Participants Points Earned <span><?php echo $total_points; ?> pts</span></div>
                    </div>

                    <div class="split-card">
                        <h3>🌟 Event Recommendation Section</h3>
                        <?php if ($recommended_events && mysqli_num_rows($recommended_events) > 0): ?>
                            <?php while ($rec = mysqli_fetch_assoc($recommended_events)): ?>
                                <div class="rec-item">
                                    <div class="rec-title">🔥 [Club ID: <?php echo htmlspecialchars($clubID); ?>] <?php echo htmlspecialchars($rec['event_title']); ?></div>
                                    <div class="rec-meta">Location: <?php echo htmlspecialchars($rec['event_venue']); ?> | Date: <?php echo date('d M Y', strtotime($rec['event_date'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="color: #94a3b8; font-size: 13px; font-style: italic;">No recommendations at this time.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="split-card" style="margin-top: 25px;">
                    <h3>📈 Activity Section</h3>
                    <div class="chart-placeholder" style="height: 160px;">
                        <strong>[Participant Trend Chart Canvas Component Layer]</strong>
                        <span style="font-size:11px; color:#94a3b8;">Visualizing Monthly Engagement Distributions</span>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>