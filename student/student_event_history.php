<?php
require_once 'student_login_materials.php';

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


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event History - FK Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        h2, h3 { color: #2c3e50; margin-bottom: 20px; }

        .summary-dashboard { display: grid; grid-template-columns: repeat(3, minmax(350px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; flex-direction: column; }

        .card-label { font-size: 0.9rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .card-value { font-size: 1.8rem; font-weight: bold; color: #0f172a; }

        .table-container { background: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 40px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { background-color: #f8fafc; color: #475569; font-weight: 600; padding: 15px 20px; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background-color: #f1f5f9; }

        .action-link { color: #3b82f6; text-decoration: none; font-weight: 600; }
        .action-link:hover { text-decoration: underline; color: #2563eb; }

        .chart-wrapper { background: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; height: 350px; position: relative; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    
    <div class="content-area">
        <h2>Event History for <?php echo htmlspecialchars($stu_name); ?></h2>

        <div class="summary-dashboard">
            <div class="summary-card">
                <span class="card-label">Total Events Joined</span>
                <span class="card-value"><?php echo $total_joined; ?></span>
            </div>
            <div class="summary-card">
                <span class="card-label">Recognition Level</span>
                <span class="card-value"><?php echo htmlspecialchars($rec_level); ?></span>
            </div>
            <div class="summary-card">
                <span class="card-label">Participants Points</span>
                <span class="card-value"><?php echo $total_points; ?></span>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['event_title']); ?></td>
                    <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($row['registration_status']); ?>
                    </td>
                    <td><a href="student_view_event_details.php?id=<?php echo $row['eventID']; ?>" class="action-link">View Details</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <h3>Participant Trend</h3>
        <div class="chart-wrapper">
            <canvas id="historyChart"></canvas>
        </div>
    </div>

    <script>
        new Chart(document.getElementById('historyChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($event_titles); ?>,
                datasets: [{ 
                    label: 'Points Earned', 
                    data: <?php echo json_encode($event_points); ?>, 
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)', 
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointRadius: 4,
                    fill: true 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, 
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>