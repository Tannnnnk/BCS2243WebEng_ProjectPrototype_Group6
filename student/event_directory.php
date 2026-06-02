<?php
// basic debugging setup for local testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'student_login_materials.php';

// define pro logic
$target_file = __DIR__ . '/../uploads/' . $photo_path;
if (!empty($photo_path) && file_exists($target_file)) {
    $img_src = "../uploads/" . htmlspecialchars($photo_path);
} else {
    $img_src = "../images/default-avatar.png"; 
}

// format username display line
$username_display = '<img src="' . $img_src . '" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; display: inline-block; vertical-align: middle; margin-right: 12px; border: 2px solid #fff;">' . htmlspecialchars($stu_name);

// check if the current user is a committee member 
$check_comm_sql = "SELECT m.*, mr.m_role_desc FROM membership m 
                   JOIN membershiprole mr ON m.roleID = mr.roleID 
                   WHERE m.userID = '$userID' AND m.roleID <= 'R08' LIMIT 1";
$comm_run = mysqli_query($link, $check_comm_sql);
// Check if the session variable exists, and if it specifically equals 'Committee'
$is_comm = (isset($_SESSION['active_role']) && $_SESSION['active_role'] === 'Committee');

$committee_role = "General Student";
if ($is_comm) {
    $c_row = mysqli_fetch_assoc($comm_run);
    $committee_role = $c_row['m_role_desc'];
}


$msg = "";
$msg_type = "";

// get club id from url parameter, fallback to database if missing
$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';

if (empty($clubID)) {
    $fallback = mysqli_query($link, "
    SELECT m.clubID
    FROM club c
    INNER JOIN membership m ON c.clubID = m.clubID
    INNER JOIN committee cm ON m.memberID = cm.memberID
    INNER JOIN events e ON cm.eventID = e.eventID
    WHERE m.userID = '$userID'
    LIMIT 1
    ");

    if ($fallback && $f_row = mysqli_fetch_assoc($fallback)) {
        $clubID = $f_row['clubID'];
    }
}

// registration processing block
if (isset($_POST['register_event'])) {
    $eventID = mysqli_real_escape_string($link, $_POST['eventID']);
    $today_date = date('Y-m-d');
    
    // prevent multiple registrations
    $dup_check = mysqli_query($link, "SELECT * FROM eventregistration WHERE userID = '$userID' AND eventID = '$eventID'");
    if (mysqli_num_rows($dup_check) > 0) {
        $msg = "⚠️ You are already registered for this event.";
        $msg_type = "error";
    } else {
        $insert_sql = "INSERT INTO eventregistration (userID, eventID, registration_date, registration_status) 
                       VALUES ('$userID', '$eventID', '$today_date', 'Confirmed')";
        if (mysqli_query($link, $insert_sql)) {
            $msg = "🎉 Successfully registered for the event!";
            $msg_type = "success";
        } else {
            $msg = "❌ Error processing registration entry.";
            $msg_type = "error";
        }
    }
}

// delete event block (only allowed for committee users)
if (isset($_GET['delete_id']) && $is_comm) {
    $del_id = mysqli_real_escape_string($link, $_GET['delete_id']);
    if (mysqli_query($link, "DELETE FROM events WHERE eventID = '$del_id'")) {
        $msg = "✅ Event successfully removed.";
        $msg_type = "success";
    } else {
        $msg = "❌ Error deleting event.";
        $msg_type = "error";
    }
}

// database metrics collection
$total_events = 0;
$q1 = mysqli_query($link, "SELECT COUNT(*) as total FROM events");
if ($q1) { $total_events = mysqli_fetch_assoc($q1)['total']; }

$total_pax = 0;
$q2 = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration");
if ($q2) { $total_pax = mysqli_fetch_assoc($q2)['total']; }

$my_reg_count = 0;
$q3 = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration WHERE userID = '$userID'");
if ($q3) { $my_reg_count = mysqli_fetch_assoc($q3)['total']; }

$upcoming_count = 0;
$q4 = mysqli_query($link, "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()");
if ($q4) { $upcoming_count = mysqli_fetch_assoc($q4)['total']; }

// point system summation
$total_points = 0;
$q5 = mysqli_query($link, "SELECT SUM(point_value) as points FROM points WHERE userID = '$userID'");
if ($q5 && $p_row = mysqli_fetch_assoc($q5)) {
    $total_points = $p_row['points'] ? $p_row['points'] : 0;
}

// general data pulling queries
$events_result = mysqli_query($link, "SELECT * FROM events ORDER BY event_date ASC");
$rec_result = mysqli_query($link, "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3");

?>
<?php

$events_data = mysqli_query($link, "SELECT * FROM events");
$category_counts = [];
$month_counts = [];

while ($row = mysqli_fetch_assoc($events_data)) {
    // 1. Group by Club ID (with safety check)
    // We use ?? 'Unassigned' to catch cases where clubID is null/missing
    $cid = $row['clubID'] ?? 'Unassigned'; 
    $category_counts[$cid] = ($category_counts[$cid] ?? 0) + 1;

    // 2. Group by Month
    // We also check if event_date exists to avoid errors on bad data
    if (!empty($row['event_date'])) {
        $month = date('F', strtotime($row['event_date']));
        $month_counts[$month] = ($month_counts[$month] ?? 0) + 1;
    }
}
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
        
        /* natural css names instead of strict numerical tracking */
        .dashboard-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .action-card { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; text-align: center; border-radius: 8px; text-decoration: none; color: #475569; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .action-card:hover { background: #ecfdf5; border-color: #10b981; color: #065f46; }
        
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th { background-color: #f8fafc; padding: 12px; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        .btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; display: inline-block; }
        .btn-register { background-color: #3b82f6; color: white; }
        .btn-register:hover { background-color: #2563eb; }
        .btn-edit { background-color: #f59e0b; color: white; margin-right: 5px; }
        .btn-delete { background-color: #ef4444; color: white; }
        .btn-info { background-color: #3b82f6; color: white; margin-right: 5px; }
        .btn-info:hover { background-color: #2563eb; }
        .btn-assign { background-color: #6366f1; color: white; }
        .btn-assign:hover { background-color: #4f46e5; }
        
        .role-indicator { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; }
        .ind-com { background-color: #fef3c7; color: #d97706; }
        .ind-stu { background-color: #e0f2fe; color: #0369a1; }
        
        .footer-split { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 5px; }
        @media(max-width: 768px) { .footer-split { grid-template-columns: 1fr; } }
        
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="workspace-wrapper">
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if ($is_comm): ?>
                <div class="central-board">
                    <div class="board-title">
                        <span>🛠️ Member Dashboard (Committee Console)</span>
                        <div>
                            <span class="role-indicator ind-com">Viewing as: <?php echo htmlspecialchars($committee_role); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-actions">
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
                                <th style="text-align:center;">Action Controls</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($events_result)): ?>
                                    <tr>
                                        <td><a href="engagement_trend.php?eventID=<?php echo $row['eventID']; ?>&clubID=<?php echo urlencode($clubID); ?>" class="btn btn-link"><?php echo htmlspecialchars($row['event_title']); ?></a></td>
                                        <td>📍 <?php echo htmlspecialchars($row['event_venue']); ?></td>
                                        <td>📅 <?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                        <td>
                                            <?php if (!empty($row['attendance_qr'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['attendance_qr']); ?>" target="_blank" class="btn btn-info" title="View QR Code">QR</a>
                                            <?php else: ?>
                                                <button class="btn" style="background: #e2e8f0; color: #a0aec0; cursor: not-allowed; margin-right: 5px;" title="No QR Assigned">QR</button>
                                            <?php endif; ?> 
                                            <a href="assign_committee.php?eventID=<?php echo $row['eventID']; ?>&clubID=<?php echo urlencode($clubID); ?>" class="btn btn-assign">Assign</a>
                                            <a href="manage_events.php?edit_id=<?php echo $row['eventID']; ?>&clubID=<?php echo urlencode($clubID); ?>" class="btn btn-edit">Edit</a>
                                            <a href="event_directory.php?delete_id=<?php echo $row['eventID']; ?>&clubID=<?php echo urlencode($clubID); ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to permanently delete this event track record?');">Delete</a>
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
                        <div class="stat-item">Upcoming Event <span><?php echo $upcoming_count; ?></span></div>
                        <div class="stat-item">Total Participants <span><?php echo $total_pax; ?></span></div>
                    </div>
                    <div class="split-card">
    <h3>📈 Chart Overview</h3>
    <div style="height: 200px; margin-bottom: 20px;">
        <canvas id="categoryChart"></canvas>
    </div>
    <div style="height: 200px;">
        <canvas id="monthlyTrendChart"></canvas>
    </div>
</div>

           <script>
// Pie Chart: Events by Club
new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_keys($category_counts)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($category_counts)); ?>,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Line Chart: Monthly Trend
new Chart(document.getElementById('monthlyTrendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($month_counts)); ?>,
        datasets: [{
            label: 'Events per Month',
            data: <?php echo json_encode(array_values($month_counts)); ?>,
            borderColor: '#6366f1',
            fill: false,
            tension: 0.1
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

        </div>
    </div>

            <?php else: ?>
                <div class="central-board">
                    <div class="board-title">
                        <span>📅 Student Dashboard Portal</span>
                        <span class="role-indicator ind-stu">Viewing as: Student</span>
                    </div>

                    <div class="dashboard-actions">
                        <a href="browse_event.php?clubID=<?php echo urlencode($clubID); ?>" class="action-card">🔍 Browse Events</a>
                        <a href="student_my_participation.php" class="action-card">📌 My Registration</a>
                        <a href="student_event_history.php" class="action-card">📌 My Event History</a>
                        
                        
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
                                <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
                                    <?php mysqli_data_seek($events_result, 0); ?>
                                    <?php while ($row = mysqli_fetch_assoc($events_result)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
                                            <td>📍 <?php echo htmlspecialchars($row['event_venue']); ?></td>
                                            <td>📅 <?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                            <td>
                                                <form method="POST" action="event_directory.php?clubID=<?php echo urlencode($clubID); ?>" style="display:inline;">
                                                    <input type="hidden" name="eventID" value="<?php echo htmlspecialchars($row['eventID']); ?>">
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
                        <div class="stat-item">Registered Events <span><?php echo $my_reg_count; ?></span></div>
                        <div class="stat-item">Upcoming Events Available <span><?php echo $upcoming_count; ?></span></div>
                        <div class="stat-item">Participants Points Earned <span><?php echo $total_points; ?> pts</span></div>
                    </div>

                    <div class="split-card">
                        <h3>🌟 Event Recommendation Section</h3>
                        <?php if ($rec_result && mysqli_num_rows($rec_result) > 0): ?>
                            <?php while ($rec_row = mysqli_fetch_assoc($rec_result)): ?>
                                <div class="rec-item">
                                    <div class="rec-title">🔥 [Club ID: <?php echo htmlspecialchars($clubID); ?>] <?php echo htmlspecialchars($rec_row['event_title']); ?></div>
                                    <div class="rec-meta">Location: <?php echo htmlspecialchars($rec_row['event_venue']); ?> | Date: <?php echo date('d M Y', strtotime($rec_row['event_date'])); ?></div>
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
        <canvas id="activityChart"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Assuming you have your data ready from the PHP logic we discussed earlier
    // This creates a Bar Chart for the trend
    new Chart(document.getElementById('activityChart'), {
        type: 'bar',
        data: {
            // Using the months and counts from your PHP processing
            labels: <?php echo json_encode(array_keys($month_counts)); ?>,
            datasets: [{
                label: 'Monthly Engagement',
                data: <?php echo json_encode(array_values($month_counts)); ?>,
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>