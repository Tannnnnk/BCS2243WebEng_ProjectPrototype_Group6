<?php
session_start();
require_once '../db_connection.php';

$userID = $_SESSION['userID'];

// 1. Fetch Student Name
$stu_res = mysqli_query($link, "SELECT stu_name FROM students WHERE userID = '$userID'");
$stu_row = mysqli_fetch_assoc($stu_res);
$stu_name = $stu_row['stu_name'] ?? 'Student';

// 2. Fetch History Data 
// We include the registration to count total events
$sql = "SELECT e.eventID, e.event_title, e.event_date, er.registration_status 
        FROM eventregistration er
        JOIN events e ON er.eventID = e.eventID
        WHERE er.userID = '$userID'
        ORDER BY e.event_date DESC";
$result = mysqli_query($link, $sql);

// 3. REVISED STATS: Get count and points dynamically
// We use a LEFT JOIN or aggregate the points table properly
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM eventregistration WHERE userID = '$userID') as total_events,
                (SELECT SUM(point_value) FROM points WHERE userID = '$userID') as total_points";
$stats_res = mysqli_query($link, $stats_sql);
$stats = mysqli_fetch_assoc($stats_res);

$total_joined = $stats['total_events'] ?? 0;
$total_points = $stats['total_points'] ?? 0;
$rec_level = ($total_points >= 20) ? "Gold" : "Silver"; 
$active_role = "Student";

include 'student_background.php'; 

// Prepare data for the chart
$event_titles = [];
$event_points = [];
// We need to fetch points per event to show a trend
$points_detail = mysqli_query($link, "SELECT e.event_title, p.point_value 
                                      FROM points p 
                                      JOIN attendance a ON p.attendanceID = a.attendanceID 
                                      JOIN events e ON a.eventID = e.eventID 
                                      WHERE p.userID = '$userID'");
while($p = mysqli_fetch_assoc($points_detail)) {
    $event_titles[] = $p['event_title'];
    $event_points[] = $p['point_value'];
}
?>

<div class="content-area">
    <h2>Event History for <?php echo htmlspecialchars($stu_name); ?></h2>
    
    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div style="border: 1px solid #ddd; padding: 15px; flex: 1; background: #f8fafc;">Total Events Joined: <strong><?php echo $total_joined; ?></strong></div>
        <div style="border: 1px solid #ddd; padding: 15px; flex: 1; background: #f8fafc;">Recognition Level: <strong><?php echo $rec_level; ?></strong></div>
        <div style="border: 1px solid #ddd; padding: 15px; flex: 1; background: #f8fafc;">Participants Points: <strong><?php echo $total_points; ?></strong></div>
    </div>

    <table border="1" style="width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 20px;">
        <tr style="background-color: #e2e8f0;">
            <th>Event Name</th><th>Date</th><th>Status</th><th>Action</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['event_title']); ?></td>
            <td><?php echo htmlspecialchars($row['event_date']); ?></td>
            <td><?php echo htmlspecialchars($row['registration_status']); ?></td>
            <td><a href="student_view_event_details.php?id=<?php echo $row['eventID']; ?>">View</a></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h3>Participant Trend</h3>
    <div style="height: 200px; width: 100%; border: 1px solid #eee; padding: 10px;">
        <canvas id="historyChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('historyChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($event_titles); ?>,
            datasets: [{ 
                label: 'Points Earned', 
                data: <?php echo json_encode($event_points); ?>, 
                borderColor: '#3b82f6',
                fill: false 
            }]
        }
    });
</script>