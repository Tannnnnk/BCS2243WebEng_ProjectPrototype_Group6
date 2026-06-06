<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'student_login_materials.php';

// --- SECURITY & SCOPE FILTERING ---
$events_list = mysqli_query($link, "SELECT DISTINCT e.eventID, e.event_title 
                                    FROM events e
                                    JOIN committee cm ON e.eventID = cm.eventID
                                    JOIN membership m ON cm.memberID = m.memberID
                                    WHERE m.userID = '$userID'");

// Capture Filters
$filter_event = isset($_POST['filter_event']) ? mysqli_real_escape_string($link, $_POST['filter_event']) : '';
$start_date   = isset($_POST['start_date']) ? mysqli_real_escape_string($link, $_POST['start_date']) : '';
$end_date     = isset($_POST['end_date']) ? mysqli_real_escape_string($link, $_POST['end_date']) : '';

// --- CORE ANALYTICS MATRIX QUERY ---
$query_string = "SELECT DISTINCT
                    r.userID, 
                    s.stu_ID,
                    s.stu_name, 
                    e.event_title, 
                    e.event_date,
                    COALESCE(a.attendance_status, 'Absent') AS att_status,
                    COALESCE(p.point_value, 0) AS point_score
                 FROM eventregistration r
                 JOIN students s ON r.userID = s.userID
                 JOIN events e ON r.eventID = e.eventID
                 LEFT JOIN attendance a ON r.eventID = a.eventID AND r.userID = a.userID
                 LEFT JOIN points p ON r.userID = p.userID AND a.attendanceID = p.attendanceID
                 JOIN committee cm ON e.eventID = cm.eventID
                 JOIN membership m ON cm.memberID = m.memberID
                 WHERE m.userID = '$userID'";

if (!empty($filter_event)) $query_string .= " AND r.eventID = '$filter_event'";
if (!empty($start_date))   $query_string .= " AND e.event_date >= '$start_date'";
if (!empty($end_date))     $query_string .= " AND e.event_date <= '$end_date'";

$report_results = mysqli_query($link, $query_string);

// --- METRIC AGGREGATION ---
$present_count = 0;
$absent_count = 0;
$monthly_trends = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];

if ($report_results) {
    while ($row = mysqli_fetch_assoc($report_results)) {
        $month = date('M', strtotime($row['event_date']));
        if (array_key_exists($month, $monthly_trends)) $monthly_trends[$month]++;
        
        if ($row['att_status'] == 'Present') $present_count++;
        else $absent_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Page Workspace</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-layout { display: flex; flex-direction: column; gap: 24px; font-family: system-ui, sans-serif; }
        .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; justify-content: space-between; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 20px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8fafc; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 12px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .charts-container-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .chart-box-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; }
        .chart-label { font-size: 12px; font-weight: bold; color: #64748b; margin-bottom: 10px; text-transform: uppercase; text-align: center; }
        .btn { padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; border: none; }
        .btn-primary { background: #2563eb; color: white; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="report-layout">
            <form method="POST" class="workspace-card">
                <div class="board-title"><span>📊 Report Page Workspace</span></div>
                <div class="filter-grid">
                    <div><label>Event</label><select name="filter_event" class="form-control"><option value="">All Events</option><?php mysqli_data_seek($events_list, 0); while($ev = mysqli_fetch_assoc($events_list)): ?><option value="<?php echo $ev['eventID']; ?>" <?php echo ($filter_event == $ev['eventID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ev['event_title']); ?></option><?php endwhile; ?></select></div>
                    <div><label>Start Date</label><input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control"></div>
                    <div><label>End Date</label><input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control"></div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn btn-primary">Filter Report</button>
                    <button type="button" class="btn btn-primary" style="background: #059669;" onclick="window.print()">Generate Report</button>
                </div>
                
                <table>
                    <thead><tr><th>StudentID</th><th>Student Name</th><th>Event</th><th>Attendance</th><th>Points</th></tr></thead>
                    <tbody>
                        <?php if (mysqli_num_rows($report_results) > 0): mysqli_data_seek($report_results, 0); while($row = mysqli_fetch_assoc($report_results)): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($row['stu_ID']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($row['stu_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['event_title']); ?></td>
                            <td><?php echo $row['att_status']; ?></td>
                            <td>+<?php echo $row['point_score']; ?> XP</td>
                        </tr>
                        <?php endwhile; else: ?><tr><td colspan="5">No records found.</td></tr><?php endif; ?>
                    </tbody>
                </table>

                <div class="charts-container-row">
                    <div class="chart-box-card">
                        <div class="chart-label">Monthly Event Trend</div>
                        <canvas id="trendChart"></canvas>
                    </div>
                    <div class="chart-box-card">
                        <div class="chart-label">Participants Rate</div>
                        <canvas id="rateChart"></canvas>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        new Chart(document.getElementById('trendChart'), { type: 'bar', data: { labels: <?php echo json_encode(array_keys($monthly_trends)); ?>, datasets: [{ label: 'Registrations', data: <?php echo json_encode(array_values($monthly_trends)); ?>, backgroundColor: '#2563eb' }] } });
        new Chart(document.getElementById('rateChart'), { type: 'doughnut', data: { labels: ['Present', 'Absent'], datasets: [{ data: [<?php echo $present_count; ?>, <?php echo $absent_count; ?>], backgroundColor: ['#10b981', '#ef4444'] }] } });
    </script>
</body>
</html>