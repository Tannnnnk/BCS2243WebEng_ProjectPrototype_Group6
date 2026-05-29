<?php
require_once 'student_login_materials.php';

$points_query = "SELECT SUM(point_value) AS calculated_total FROM points WHERE userID = '$userID'";
$points_result = mysqli_query($link, $points_query);

$total_points = 0;
if ($points_result && mysqli_num_rows($points_result) > 0) {
    $row = mysqli_fetch_assoc($points_result);
    $total_points = $row['calculated_total'] ? (int)$row['calculated_total'] : 0; 
}

$rank_query = "
    SELECT COUNT(*) + 1 AS ranking 
    FROM (
        SELECT userID, SUM(point_value) as total_pts 
        FROM points 
        GROUP BY userID
    ) AS user_totals 
    WHERE total_pts > $total_points
";
$rank_result = mysqli_query($link, $rank_query);

$user_ranking = "-";
if ($rank_result && mysqli_num_rows($rank_result) > 0) {
    $row = mysqli_fetch_assoc($rank_result);
    $user_ranking = "#" . $row['ranking'];
}

$history_query = "SELECT e.event_title, e.event_date, e.event_time, a.attendance_status 
                  FROM attendance a
                  JOIN events e ON a.eventID = e.eventID
                  WHERE a.userID = '$userID'
                  ORDER BY e.event_date DESC, e.event_time DESC";
$history_result = mysqli_query($link, $history_query);

$recognition_level = "";
$enforcement_msg = "";
$level_color_class = "";

if ($total_points >= 80) {
    $recognition_level = "Outstanding Participant";
    $enforcement_msg = "Eligible for leadership award / priority in event registration";
    $level_color_class = "text-purple";
} elseif ($total_points >= 50) {
    $recognition_level = "Highly Active Student";
    $enforcement_msg = "Eligible for active student award / bonus points";
    $level_color_class = "text-green";
} elseif ($total_points >= 20) {
    $recognition_level = "Active Participant";
    $enforcement_msg = "Eligible for participation certificate";
    $level_color_class = "text-blue";
} else {
    $recognition_level = "Warning";
    $enforcement_msg = "Reminder to participate more";
    $level_color_class = "text-red";
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Participation Dashboard - FK Management System</title>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 30px 20px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); }
        .enforcement_msg { font-size: 13px; color: #6b7280; line-height: 1.4; padding: 0 10px; }

        .stat-title { font-size: 16px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .stat-value { font-size: 36px; font-weight: 800; }

        .text-green { color: #10b981; } 
        .text-blue { color: #3b82f6; } 

        .history-card { background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }

        .history-title { font-size: 20px; font-weight: bold; color: #1f2937; margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        .history-table { width: 100%; border-collapse: collapse; text-align: left; }
        .history-table th { background-color: #f9fafb; color: #4b5563; font-size: 14px; font-weight: 600; padding: 12px 15px; border-bottom: 2px solid #e5e7eb; }
        .history-table td { padding: 15px; font-size: 15px; color: #374151; border-bottom: 1px solid #e5e7eb; }
        .history-table tbody tr:hover { background-color: #f0fdf4; }

        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: capitalize; }
        .status-badge.present { background-color: #d1fae5; color: #065f46; } 
        .status-badge.volunteer { background-color: #dbeafe; color: #1e40af; } 
        .status-badge.late { background-color: #fef3c7; color: #92400e; } 
        .status-badge.absent { background-color: #fee2e2; color: #b91c1c; } 

        .empty-state { text-align: center; color: #9ca3af; font-style: italic; padding: 30px !important; }

        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .history-card { padding: 15px; }
            .stat-value { font-size: 32px; }
        }
        
        .text-purple { color: #8b5cf6; } 
        .text-green { color: #10b981; } 
        .text-blue { color: #3b82f6; } 
        .text-red { color: #ef4444; } 
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Your points</div>
                <div class="stat-value text-green"><?php echo $total_points; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Your ranking</div>
                <div class="stat-value text-blue"><?php echo $user_ranking; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-title">Recognition Status</div>
                <div class="stat-value <?php echo $level_color_class; ?>" style="font-size: 22px; margin-bottom: 8px;">
                    <?php echo $recognition_level; ?>
                </div>
                <div class="enforcement_msg">
                    <?php echo $enforcement_msg; ?>
                </div>
            </div>
        </div>

        <div class="history-card">
            <div class="history-title">Participation History</div>
            
            <div class="table-responsive">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Event name</th>
                            <th>Event date</th>
                            <th>Event time</th>
                            <th>Attendance status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($history_result && mysqli_num_rows($history_result) > 0) {
                            while ($event = mysqli_fetch_assoc($history_result)) {
                                $formatted_date = date("d M Y", strtotime($event['event_date']));
                                $formatted_time = date("h:i A", strtotime($event['event_time']));
                                
                                $status = htmlspecialchars($event['attendance_status']);
                                $badge_class = strtolower($status); 
                                
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($event['event_title']) . "</strong></td>";
                                echo "<td>" . $formatted_date . "</td>";
                                echo "<td>" . $formatted_time . "</td>";
                                echo "<td><span class='status-badge {$badge_class}'>{$status}</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='empty-state'>No participation history found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</body>
</html>