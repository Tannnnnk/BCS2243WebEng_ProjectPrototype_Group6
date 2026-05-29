<?php
require_once 'admin_login_materials.php';

$filter_club = isset($_GET['filter_club']) ? mysqli_real_escape_string($link, $_GET['filter_club']) : '';
$filter_sem  = isset($_GET['filter_sem']) ? mysqli_real_escape_string($link, $_GET['filter_sem']) : '';
// 1. Get ALL clubs for the filter dropdown
$clubs_list_query = "SELECT DISTINCT clubID, club_name 
                     FROM club
                     ORDER BY club_name ASC";
$clubs_list_result = mysqli_query($link, $clubs_list_query);

// 2. Get ALL semesters for the filter dropdown
$sem_list_query = "SELECT DISTINCT semesterID, year_sem, start_date, end_date
                   FROM semester
                   ORDER BY semesterID ASC";
                   
$sem_list_result = mysqli_query($link, $sem_list_query);
$safe_start = '1970-01-01'; 
$safe_end   = '2030-12-31'; 

if ($filter_sem !== '') {
    // If the user selected a specific semester, fetch ITS specific dates!
    $date_query = "SELECT start_date, end_date FROM semester WHERE semesterID = '$filter_sem' LIMIT 1";
    $date_result = mysqli_query($link, $date_query);
    
    if ($date_result && mysqli_num_rows($date_result) > 0) {
        $date_row = mysqli_fetch_assoc($date_result);
        $safe_start = $date_row['start_date'];
        $safe_end   = $date_row['end_date'];
    }
} else {
    // OPTIONAL FALLBACK: If "All Active Semesters" is selected, find the earliest start 
    // and latest end date in your system so the dashboard displays everything.
    $fallback_query = "SELECT MIN(start_date) as min_start, MAX(end_date) as max_end FROM semester";
    $fallback_result = mysqli_query($link, $fallback_query);
    if ($fallback_result) {
        $fallback_row = mysqli_fetch_assoc($fallback_result);
        $safe_start = $fallback_row['min_start'] ?? '1970-01-01';
        $safe_end   = $fallback_row['max_end'] ?? '2030-12-31';
    }
}

// --- ROW 1 QUERIES ---

// Box 1: Total number of events conducted
// Build the base query string joining events to committee and membership
$sql_total = "
    SELECT COUNT(DISTINCT e.eventID) as total 
    FROM events e
    INNER JOIN committee cm ON e.eventID = cm.eventID
    INNER JOIN membership m ON cm.memberID = m.memberID
    WHERE (e.event_date BETWEEN '$safe_start' AND '$safe_end')
";

// If a specific club filter is selected, we can now safely append the clubID check
if (!empty($filter_club)) {
    $sql_total .= " AND m.clubID = '$filter_club'";
}

$q_total_events = mysqli_query($link, $sql_total);
$total_events = mysqli_fetch_assoc($q_total_events)['total'] ?? 0;

// Box 2: Total student participation (Unique students present)
$sql_text = "
    SELECT COUNT(DISTINCT er.userID) as total 
    FROM eventregistration er
    INNER JOIN events e ON er.eventID = e.eventID 
    INNER JOIN committee cm ON e.eventID = cm.eventID
    INNER JOIN membership m ON cm.memberID = m.memberID
    WHERE (e.event_date BETWEEN '$safe_start' AND '$safe_end')
";

if (!empty($filter_club)) {
    $sql_text .= " AND m.clubID = '$filter_club'";
}

$sql_total_participation = mysqli_query($link, $sql_text);

if ($sql_total_participation) {
    $total_participation = mysqli_fetch_assoc($sql_total_participation)['total'] ?? 0;
} else {
    $total_participation = 0;
}

// Box 3: Attendance rate per club
$club_rates = [];
$sql_club_rate = "
    SELECT c.club_name, 
           COUNT(DISTINCT er.userID) as total_registered,
           COUNT(DISTINCT CASE WHEN a.attendance_status IN ('Present', 'Late') THEN a.userID ELSE NULL END) as attended
    FROM club c
    JOIN membership m ON c.clubID = m.clubID
    JOIN committee cm ON m.memberID = cm.memberID
    JOIN events e ON cm.eventID = e.eventID
    LEFT JOIN eventregistration er ON e.eventID = er.eventID
    LEFT JOIN attendance a ON er.userID = a.userID AND er.eventID = a.eventID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end'
";

if (!empty($filter_club)) {
    $sql_club_rate .= " AND m.clubID = '$filter_club'";
}
$sql_club_rate .= " GROUP BY c.clubID, c.club_name ORDER BY c.club_name ASC";
$q_club_rate = mysqli_query($link, $sql_club_rate);
if ($q_club_rate) {
    while ($row = mysqli_fetch_assoc($q_club_rate)) {
        $rate = ($row['total_registered'] > 0) ? round(($row['attended'] / $row['total_registered']) * 100, 1) : 0;
        $club_rates[] = ['name' => $row['club_name'], 'rate' => $rate];
    }
}

// =========================================================================

// --- ROW 2 QUERIES ---
// Box 4: Attendance rate per event
$event_rates = [];
$sql_event_rate = "
    SELECT e.event_title, 
           COUNT(er.userID) as total_registered,
           SUM(CASE WHEN a.attendance_status IN ('Present', 'Late') THEN 1 ELSE 0 END) as attended
    FROM events e
    LEFT JOIN eventregistration er ON e.eventID = er.eventID
    LEFT JOIN attendance a ON er.userID = a.userID AND er.eventID = a.eventID
    INNER JOIN committee cm ON e.eventID = cm.eventID
    INNER JOIN membership m ON cm.memberID = m.memberID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end'
";

if (!empty($filter_club)) {
    $sql_event_rate .= " AND m.clubID = '$filter_club'";
}

$sql_event_rate .= " GROUP BY e.event_title ORDER BY e.event_title ASC";
$q_event_rate = mysqli_query($link, $sql_event_rate);
if ($q_event_rate) {
    while ($row = mysqli_fetch_assoc($q_event_rate)) {
        $rate = ($row['total_registered'] > 0) ? round(($row['attended'] / $row['total_registered']) * 100, 1) : 0;
        $event_rates[] = ['name' => $row['event_title'], 'rate' => $rate];
    }
}

// Box 5: Most active students (Top 5 by attendance count)
$active_students = [];
$sql_active_students = "
    SELECT s.stu_name, COUNT(a.attendanceID) as attendance_count
    FROM students s
    JOIN attendance a ON s.userID = a.userID
    JOIN events e ON a.eventID = e.eventID
    JOIN committee cm ON e.eventID = cm.eventID
    JOIN membership m ON cm.memberID = m.memberID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end' AND a.attendance_status IN ('Present', 'Late')
";

if (!empty($filter_club)) {
    $sql_active_students .= " AND m.clubID = '$filter_club'";
}

$sql_active_students .= " GROUP BY s.userID, s.stu_name ORDER BY attendance_count DESC LIMIT 5";
$q_active_students = mysqli_query($link, $sql_active_students);
if ($q_active_students) {
    while ($row = mysqli_fetch_assoc($q_active_students)) {
        $active_students[] = $row;
    }
}

// Box 6: Most active clubs (Top 5 by event count)
$active_clubs = [];
$sql_active_clubs = "
    SELECT c.club_name, COUNT(DISTINCT club_events.eventID) as event_count
    FROM club c
    JOIN (
        SELECT DISTINCT m.clubID, cm.eventID
        FROM membership m
        JOIN committee cm ON m.memberID = cm.memberID
    ) AS club_events ON c.clubID = club_events.clubID
    JOIN events e ON club_events.eventID = e.eventID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end'
";

if (!empty($filter_club)) {
    $sql_active_clubs .= " AND club_events.clubID = '$filter_club'";
}

$sql_active_clubs .= " GROUP BY c.clubID ORDER BY event_count DESC LIMIT 5";
$q_active_clubs = mysqli_query($link, $sql_active_clubs);
if ($q_active_clubs) {
    while ($row = mysqli_fetch_assoc($q_active_clubs)) {
        $active_clubs[] = $row;
    }
}

// --- ROW 3 QUERIES (FOR CHARTS) ---

// Chart 1: Point Distribution (Sum of points by club)
$chart_points_labels = []; $chart_points_data = [];
$sql_points = "
    SELECT c.club_name, IFNULL(SUM(p.point_value), 0) as total_points
    FROM club c
    JOIN (
        SELECT DISTINCT m.clubID, cm.eventID
        FROM membership m
        JOIN committee cm ON m.memberID = cm.memberID
    ) AS club_events ON c.clubID = club_events.clubID
    JOIN events e ON club_events.eventID = e.eventID
    JOIN attendance a ON e.eventID = a.eventID
    JOIN points p ON a.attendanceID = p.attendanceID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end'
";

if (!empty($filter_club)) {
    $sql_points .= " AND club_events.clubID = '$filter_club'";
}

$sql_points .= " GROUP BY c.clubID ORDER BY total_points DESC";
$q_points = mysqli_query($link, $sql_points);
if ($q_points) {
    while ($row = mysqli_fetch_assoc($q_points)) {
        $chart_points_labels[] = $row['club_name'];
        $chart_points_data[] = $row['total_points'];
    }
}

// Chart 2: Participation Trend (Attendances grouped by Date)
$chart_trend_labels = []; $chart_trend_data = [];
$sql_trend = "
    SELECT e.event_date, COUNT(a.attendanceID) as daily_attendance
    FROM events e
    JOIN attendance a ON e.eventID = a.eventID
    JOIN committee cm ON e.eventID = cm.eventID
    JOIN membership m ON cm.memberID = m.memberID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end' AND a.attendance_status IN ('Present', 'Late')
";

if (!empty($filter_club)) {
    $sql_trend .= " AND m.clubID = '$filter_club'";
}

$sql_trend .= " GROUP BY e.event_date ORDER BY e.event_date ASC";
$q_trend = mysqli_query($link, $sql_trend);
if ($q_trend) {
    while ($row = mysqli_fetch_assoc($q_trend)) {
        $chart_trend_labels[] = $row['event_date'];
        $chart_trend_data[] = $row['daily_attendance'];
    }
}

// Chart 3: Engagement Ranking (Top 5 events with most attendees)
$chart_engage_labels = []; $chart_engage_data = [];
$sql_engage = "
    SELECT e.event_title, COUNT(a.attendanceID) as attendees
    FROM events e
    JOIN attendance a ON e.eventID = a.eventID
    JOIN committee cm ON e.eventID = cm.eventID
    JOIN membership m ON cm.memberID = m.memberID
    WHERE e.event_date BETWEEN '$safe_start' AND '$safe_end' AND a.attendance_status IN ('Present', 'Late')
";

if (!empty($filter_club)) {
    $sql_engage .= " AND m.clubID = '$filter_club'";
}

$sql_engage .= " GROUP BY e.eventID ORDER BY attendees DESC LIMIT 5";
$q_engage = mysqli_query($link, $sql_engage);
if ($q_engage) {
    while ($row = mysqli_fetch_assoc($q_engage)) {
        $chart_engage_labels[] = substr($row['event_title'], 0, 15) . '...'; 
        $chart_engage_data[] = $row['attendees'];
    }
}

$query = "SELECT e.*, c.club_name, s.*
          FROM events e
          JOIN committee cm ON e.eventID = cm.eventID
          JOIN membership m ON cm.memberID = m.memberID
          JOIN club c ON m.clubID = c.clubID
          JOIN semester s ON c.clubID = s.clubID
          WHERE 1=1";

if ($filter_club !== '') {
    $query .= " AND c.clubID = '$filter_club'";
}
if ($filter_sem !== '') {
    $query .= " AND s.semesterID = '$filter_sem'";
}

$query .= " ORDER BY e.event_date DESC";
$result = mysqli_query($link, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($link));
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participation & Attendance Dashboard - FK Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-container { background-color: #ffffff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; gap: 15px; align-items: center; }
        .filter-container select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }

        .btn-filter { background-color: #3b82f6; color: white; border: none; padding: 9px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-clear { color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; }
        .btn-clear:hover { text-decoration: underline; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; flex-direction: column; border-top: 3px solid #10b981; }
        .card h3 { margin: 0 0 15px 0; font-size: 15px; color: #374151; text-align: center; }

        .big-number { font-size: 40px; font-weight: bold; color: #10b981; text-align: center; margin: auto; }
        .data-list { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-list th { background: #f8fafc; padding: 8px; text-align: left; border-bottom: 2px solid #e2e8f0; color: #4a5568;}
        .data-list td { padding: 8px; border-bottom: 1px solid #e2e8f0; color: #1f2937;}
        
        .list-container { overflow-y: auto; max-height: 180px; }
        .chart-container { position: relative; height: 200px; width: 100%; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    
    <div class="content-area">
        
        <form action="participation_and_attendance_dashboard.php" method="GET" class="filter-container">
            <select name="filter_club">
                <option value="">-- All Active Clubs --</option>
                <?php
                if ($clubs_list_result && mysqli_num_rows($clubs_list_result) > 0) {
                    while ($club = mysqli_fetch_assoc($clubs_list_result)) {
                        $selected = ($filter_club == $club['clubID']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($club['clubID']) . "' $selected>" . htmlspecialchars($club['club_name']) . "</option>";
                    }
                }
                ?>
            </select>

            <select name="filter_sem">
                <option value="">-- All Active Semesters --</option>
                <?php
                if ($sem_list_result && mysqli_num_rows($sem_list_result) > 0) {
                    mysqli_data_seek($sem_list_result, 0);
                    while ($sem = mysqli_fetch_assoc($sem_list_result)) {
                        $selected = ($filter_sem == $sem['semesterID']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($sem['semesterID']) . "' $selected>" . htmlspecialchars($sem['year_sem']) . "</option>";
                    }
                }
                ?>
            </select>

            <button type="submit" class="btn-filter">Filter</button>
            <a href="participation_and_attendance_dashboard.php" class="btn-clear">Clear Filters</a>
        </form>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Total number of events conducted</h3>
                <div class="big-number"><?= $total_events ?></div>
            </div>

            <div class="card">
                <h3>Total student participation in events</h3>
                <div class="big-number"><?= $total_participation ?></div>
            </div>

            <div class="card">
                <h3>Attendance rate per clubs</h3>
                <div class="list-container">
                    <table class="data-list">
                        <tr><th>Club name</th><th>Rate</th></tr>
                        <?php foreach($club_rates as $c): ?>
                            <tr><td><?= htmlspecialchars($c['name']) ?></td><td><?= $c['rate'] ?>%</td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Attendance rate per events</h3>
                <div class="list-container">
                    <table class="data-list">
                        <tr><th>Event name</th><th>Rate</th></tr>
                        <?php foreach($event_rates as $e): ?>
                            <tr><td><?= htmlspecialchars($e['name']) ?></td><td><?= $e['rate'] ?>%</td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Most active students</h3>
                <div class="list-container">
                    <table class="data-list">
                        <tr><th>Student name</th><th>Attendances</th></tr>
                        <?php foreach($active_students as $s): ?>
                            <tr><td><?= htmlspecialchars($s['stu_name']) ?></td><td><?= $s['attendance_count'] ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Most active clubs</h3>
                <div class="list-container">
                    <table class="data-list">
                        <tr><th>Club name</th><th>Events</th></tr>
                        <?php foreach($active_clubs as $c): ?>
                            <tr><td><?= htmlspecialchars($c['club_name']) ?></td><td><?= $c['event_count'] ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Point distribution</h3>
                <div class="chart-container">
                    <canvas id="chartPoints"></canvas>
                </div>
            </div>

            <div class="card">
                <h3>Participation trend</h3>
                <div class="chart-container">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>

            <div class="card">
                <h3>Engagement ranking</h3>
                <div class="chart-container">
                    <canvas id="chartEngage"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') {
                document.querySelectorAll('.chart-container').forEach(container => {
                    container.innerHTML = '<p style="color:red; text-align:center; padding-top:50px;">Error: Could not load Chart.js. Check your internet connection.</p>';
                });
                return;
            }

            const greenColor = '#10b981';
            const blueColor = '#3b82f6';

            const pointsLabels = <?php echo json_encode($chart_points_labels); ?>;
            const pointsData = <?php echo json_encode(array_map('floatval', $chart_points_data)); ?>;

            const trendLabels = <?php echo json_encode($chart_trend_labels); ?>;
            const trendData = <?php echo json_encode(array_map('floatval', $chart_trend_data)); ?>;

            const engageLabels = <?php echo json_encode($chart_engage_labels); ?>;
            const engageData = <?php echo json_encode(array_map('floatval', $chart_engage_data)); ?>;

            // Chart 1: Point Distribution
            if (pointsData.length > 0) {
                new Chart(document.getElementById('chartPoints'), {
                    type: 'doughnut',
                    data: {
                        labels: pointsLabels,
                        datasets: [{
                            data: pointsData,
                            backgroundColor: [greenColor, blueColor, '#f59e0b', '#ef4444', '#8b5cf6']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            } else {
                document.getElementById('chartPoints').parentElement.innerHTML = '<p style="text-align:center; color:#6b7280; margin-top:80px;">No point data available</p>';
            }

            // Chart 2: Participation Trend
            if (trendData.length > 0) {
                new Chart(document.getElementById('chartTrend'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Attendances',
                            data: trendData,
                            borderColor: blueColor,
                            tension: 0.1,
                            fill: false
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        plugins: { 
                            legend: { display: false } 
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                min: 0
                            }
                        }
                    }
                });
            } else {
                document.getElementById('chartTrend').parentElement.innerHTML = '<p style="text-align:center; color:#6b7280; margin-top:80px;">No trend data available</p>';
            }

            // Chart 3: Engagement Ranking
            if (engageData.length > 0) {
                new Chart(document.getElementById('chartEngage'), {
                    type: 'bar',
                    data: {
                        labels: engageLabels,
                        datasets: [{
                            label: 'Attendees',
                            data: engageData,
                            backgroundColor: greenColor
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        plugins: { 
                            legend: { display: false } 
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            } else {
                document.getElementById('chartEngage').parentElement.innerHTML = '<p style="text-align:center; color:#6b7280; margin-top:80px;">No engagement data available</p>';
            }
        });
    </script>
</body>
</html>