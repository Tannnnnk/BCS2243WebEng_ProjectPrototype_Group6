<?php
require_once 'student_login_materials.php';

// 1. FIXED: Properly fetch and escape the eventID from the URL
$eventID = isset($_GET['eventID']) ? mysqli_real_escape_string($link, $_GET['eventID']) : '';
$clubID  = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : ''; // Grab clubID just in case you need a back button later

// 2. Initialize default variables
$event_name = "No Event Selected";
$registered = $attended = $absent = $engagement_rate = 0;

// 3. Only run the query if an eventID was actually passed
if ($eventID !== '') {
    $q_trends = mysqli_query($link, "
        SELECT 
            e.eventID,
            e.event_title,
            COUNT(DISTINCT er.userID) as total_registered,
            SUM(CASE WHEN a.attendance_status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.attendance_status = 'Late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN a.attendance_status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM events e
        LEFT JOIN eventregistration er ON e.eventID = er.eventID
        LEFT JOIN attendance a ON er.userID = a.userID AND er.eventID = a.eventID
        WHERE e.eventID = '$eventID'
        GROUP BY e.eventID, e.event_title
        LIMIT 1
    ");

    if ($q_trends && mysqli_num_rows($q_trends) > 0) {
        $trend_data = mysqli_fetch_assoc($q_trends);
        $event_name = $trend_data['event_title'];
        $registered = (int)$trend_data['total_registered'];
        $present   = (int)$trend_data['present'];
        $late       = (int)$trend_data['late'];
        $absent     = (int)$trend_data['absent'];
        
        // Present + Late are added together, then divided by Total Registered
        $engagement_rate = ($registered > 0) ? round((($present + $late) / $registered) * 100, 1) : 0;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engagement Trends - FK Management System</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .trend-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-top: 3px solid #3b82f6;
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-width: 800px; /* Prevents the card from stretching infinitely on wide screens */
            margin: 0 auto;
        }
        .trend-title-label, .trend-meta-label {
            font-family: monospace;
            color: #94a3b8;
            font-size: 11px;
            text-transform: lowercase;
            margin-bottom: -4px;
        }
        .trend-event-name {
            margin: 0;
            font-size: 20px;
            color: #1e293b;
            font-weight: 700;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .trend-section-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 14px;
        }
        .trend-status-header {
            margin: 0 0 6px 0;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }
        .trend-stat-desc {
            font-size: 13px;
            color: #64748b;
        }
        .trend-canvas-wrapper {
            position: relative;
            height: 160px;
            width: 100%;
            margin-top: 8px;
        }
        
        /* Optional: Back button styling */
        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    
    <div class="content-area">
        
        <div style="max-width: 800px; margin: 0 auto;">
            <a href="event_directory.php?clubID=<?= urlencode($clubID) ?>" class="btn-back">← Back to Event Directory</a>
        </div>

        <div class="trend-card">
            <h2 class="trend-event-name"><?= htmlspecialchars($event_name) ?></h2>
            
            <div class="trend-section-box">
                <p class="trend-status-header">Engagement trends</p>
                <div class="trend-stat-desc">
                    Overall Turnout Rate: <strong><?= $engagement_rate ?>%</strong> of total registered students.
                </div>
            </div>

            <div class="trend-section-box statistic-tool-area">
                <p class="trend-status-header">Attendance status</p>
                
                <div class="trend-canvas-wrapper">
                    <canvas id="chartEventStatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trendReg  = <?= isset($registered) ? $registered : 0 ?>;
            const trendPresent  = <?= isset($present) ? $present : 0 ?>;
            const trendLate = <?= isset($late) ? $late : 0 ?>; 
            const trendAbs  = <?= isset($absent) ? $absent : 0 ?>;

            if (trendReg > 0) {
                new Chart(document.getElementById('chartEventStatus'), {
                    type: 'bar',
                    data: {
                        labels: ['Present', 'Late', 'Absent', 'Total Slots'],
                        datasets: [{
                            data: [trendPresent, trendLate, trendAbs, trendReg],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#3b82f6'],
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Turns it into a horizontal bar chart matching layout bounds
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { beginAtZero: true, grid: { display: false } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            } else {
                document.getElementById('chartEventStatus').parentElement.innerHTML = 
                    '<p style="text-align:center; color:#94a3b8; font-size:13px; padding-top:60px;">No attendance entries tracked for this event.</p>';
            }
        });
    </script>
</body>
</html>