<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';


$userID = $_SESSION['userID'];
$username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Committee';
$role = 'Committee'; 

// --- DB QUERY: FETCH CURRENT STUDENT INFORMATION & PROFILE PICTURE ---
$photo_path = "";
$stu_name = $username; 

$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);

if ($result_profile && $row = mysqli_fetch_assoc($result_profile)) {
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
}


if (!empty($photo_path)) {
    if (strpos($photo_path, 'uploads/') === 0) {
        $img_src = "../" . htmlspecialchars($photo_path);
    } else {
        $img_src = "../uploads/" . htmlspecialchars($photo_path);
    }
} else {
    $img_src = "../images/default-avatar.png"; 
}

// Define display configuration variables for student_background component template
$display_name = $stu_name; 


$username = '<img src="' . $img_src . '" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; display: inline-block; vertical-align: middle; margin-right: 12px; border: 2px solid #ffffff;">' . htmlspecialchars($stu_name);
// ---------------------------------------------------------------------

// Capture Wireframe Filtering Parameters
$filter_event = isset($_POST['filter_event']) ? mysqli_real_escape_string($link, $_POST['filter_event']) : '';
$filter_club  = isset($_POST['filter_club']) ? mysqli_real_escape_string($link, $_POST['filter_club']) : '';
$start_date   = isset($_POST['start_date']) ? mysqli_real_escape_string($link, $_POST['start_date']) : '';
$end_date     = isset($_POST['end_date']) ? mysqli_real_escape_string($link, $_POST['end_date']) : '';

// Populate Filter Selection Menus from database dynamically
$events_list = mysqli_query($link, "SELECT eventID, event_title FROM events");
$clubs_list  = mysqli_query($link, "SELECT clubID, club_name FROM club");

// --- CORE ANALYTICS MATRIX QUERY (FIXED NO-CRASH VERSION) ---
$query_string = "SELECT 
                    r.userID, 
                    s.stu_name AS stu_name, 
                    e.event_title, 
                    COALESCE(a.attendance_status, 'Absent') AS att_status,
                    COALESCE(p.point_value, 0) AS point_score
                 FROM eventregistration r
                 JOIN students s ON r.userID = s.userID
                 JOIN events e ON r.eventID = e.eventID
                 LEFT JOIN attendance a ON r.eventID = a.eventID AND r.userID = a.userID
                 LEFT JOIN points p ON r.userID = p.userID AND a.attendanceID = p.attendanceID
                 WHERE 1=1";

if (!empty($filter_event)) {
    $query_string .= " AND r.eventID = '$filter_event'";
}
if (!empty($start_date)) {
    $query_string .= " AND e.event_date >= '$start_date'";
}
if (!empty($end_date)) {
    $query_string .= " AND e.event_date <= '$end_date'";
}

$report_results = mysqli_query($link, $query_string);

// --- METRIC AGGREGATION FOR CHARTS ENGINE ---
$present_count = 0;
$absent_count = 0;
$monthly_trends = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];

$chart_query = mysqli_query($link, "
    SELECT e.event_date, a.attendance_status 
    FROM eventregistration r
    JOIN events e ON r.eventID = e.eventID
    LEFT JOIN attendance a ON r.eventID = a.eventID AND r.userID = a.userID
");

if ($chart_query) {
    while ($c_row = mysqli_fetch_assoc($chart_query)) {
        if (isset($c_row['attendance_status']) && $c_row['attendance_status'] == 'Present') {
            $present_count++;
        } else {
            $absent_count++;
        }
        
        if (!empty($c_row['event_date'])) {
            $month = date('M', strtotime($c_row['event_date']));
            if (array_key_exists($month, $monthly_trends)) {
                $monthly_trends[$month]++;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Page Workspace</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-layout { display: flex; flex-direction: column; gap: 24px; font-family: system-ui, sans-serif; width: 100%; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px; background: #fff; color: #334155; width: 100%; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; text-align: left; background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        th { background-color: #f8fafc; padding: 14px 16px; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 16px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .badge { font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; }
        .badge-present { background-color: #d1fae5; color: #065f46; }
        .badge-absent { background-color: #fee2e2; color: #991b1b; }
        .charts-container-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .chart-box-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .chart-title { font-size: 13px; font-weight: bold; color: #475569; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; align-self: flex-start; }
        .button-bar-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        .btn { padding: 10px 22px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; }
        .btn-primary { background-color: #2563eb; color: white; }
        .btn-success { background-color: #10b981; color: white; }
        .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .role-indicator { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; background-color: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="report-layout">
            
            <form method="POST" action="report_page.php" class="workspace-card">
                <div class="board-title">
                    <span>Report Page Workspace</span>
                    <span class="role-indicator"><?php echo htmlspecialchars($role); ?></span>
                </div>

                <div class="filter-grid">
                    <div class="form-group">
                        <label>Select Event</label>
                        <select name="filter_event" class="form-control">
                            <option value="">-- All Running Events --</option>
                            <?php if ($events_list): ?>
                                <?php while($ev = mysqli_fetch_assoc($events_list)): ?>
                                    <option value="<?php echo $ev['eventID']; ?>" <?php echo ($filter_event == $ev['eventID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ev['event_title']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Club</label>
                        <select name="filter_club" class="form-control">
                            <option value="">-- All Registered Clubs --</option>
                            <?php if ($clubs_list): ?>
                                <?php while($cb = mysqli_fetch_assoc($clubs_list)): ?>
                                    <option value="<?php echo $cb['clubID']; ?>" <?php echo ($filter_club == $cb['clubID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cb['club_name']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>StudentID</th>
                                <th>Student Name</th>
                                <th>Event</th>
                                <th>Attendance</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($report_results && mysqli_num_rows($report_results) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($report_results)): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($row['userID']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($row['stu_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['event_title']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($row['att_status'] == 'Present') ? 'badge-present' : 'badge-absent'; ?>">
                                                <?php echo htmlspecialchars($row['att_status']); ?>
                                            </span>
                                        </td>
                                        <td><strong>+<?php echo htmlspecialchars($row['point_score']); ?> XP</strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">
                                        No logs or database records match the selected query criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="charts-container-row">
                    <div class="chart-box-card">
                        <div class="chart-title">Monthly Event Trend</div>
                        <canvas id="trendChartCanvas" style="max-height: 200px; width: 100%;"></canvas>
                    </div>

                    <div class="chart-box-card">
                        <div class="chart-title">Participants Rate</div>
                        <canvas id="rateChartCanvas" style="max-height: 200px; width: 100%;"></canvas>
                    </div>
                </div>

                <div class="button-bar-footer">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <button type="button" onclick="window.print()" class="btn btn-success">Download PDF</button>
                </div>
            </form>

        </div>
    </div>

    <script>
        const ctxTrend = document.getElementById('trendChartCanvas').getContext('2d');
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_trends)); ?>,
                datasets: [{
                    label: 'Registrations Logged',
                    data: <?php echo json_encode(array_values($monthly_trends)); ?>,
                    backgroundColor: '#2563eb',
                    borderRadius: 4
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } } 
            }
        });

        const ctxRate = document.getElementById('rateChartCanvas').getContext('2d');
        new Chart(ctxRate, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?php echo $present_count; ?>, <?php echo $absent_count; ?>],
                    backgroundColor: ['#10b981', '#ef4444']
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>