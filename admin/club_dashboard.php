<?php
session_start();

// 1. Security Check: Ensure only Administrators can access this page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID   = $_SESSION['userID'];
$username = $_SESSION['user_username'];

// 2. Fetch Admin Profile Info (Fixing pfp pathing)
$admin_name = $username;
$photo_path = "";
$res_admin = mysqli_query($link, "SELECT admin_name, admin_photo FROM administrator WHERE userID='$userID'");
if ($res_admin && $r = mysqli_fetch_assoc($res_admin)) {
    $admin_name = !empty($r['admin_name']) ? $r['admin_name'] : $username;
    $photo_path = !empty($r['admin_photo']) ? $r['admin_photo'] : "";
}

// ---- SUMMARY STATS ----

// Total Clubs
$total_clubs = 0;
$res = mysqli_query($link, "SELECT COUNT(*) as c FROM club");
if ($res && $r = mysqli_fetch_assoc($res)) $total_clubs = $r['c'];

// Active Clubs
$active_clubs = 0;
$res = mysqli_query($link, "SELECT COUNT(*) as c FROM club WHERE club_operational_status='Active'");
if ($res && $r = mysqli_fetch_assoc($res)) $active_clubs = $r['c'];

/**
 * FIXED LOGIC: Total Unique Members
 * We use COUNT(DISTINCT userID) so a student in 3 clubs only counts as 1 person.
 */
$total_members = 0;
$res = mysqli_query($link, "SELECT COUNT(DISTINCT userID) as c FROM membership");
if ($res && $r = mysqli_fetch_assoc($res)) $total_members = $r['c'];

/**
 * FIXED LOGIC: Committee Members
 * Counts anyone in the membership table who does NOT have the role 'Member'.
 */
$total_committees = 0;
$res = mysqli_query($link, "
    SELECT COUNT(*) as c 
    FROM membership m
    JOIN membershiprole mr ON m.roleID = mr.roleID
    WHERE mr.m_role_desc NOT IN ('Member', 'General Member')
");
if ($res && $r = mysqli_fetch_assoc($res)) $total_committees = $r['c'];


// ---- CHART DATA ----

// Chart 1: Members per Club (Top 8)
$chart_clubs  = [];
$chart_members = [];
$res = mysqli_query($link, "
    SELECT c.club_name, COUNT(m.memberID) as cnt
    FROM club c
    LEFT JOIN membership m ON c.clubID = m.clubID
    GROUP BY c.clubID ORDER BY cnt DESC LIMIT 8
");
if ($res) { 
    while ($r = mysqli_fetch_assoc($res)) { 
        $chart_clubs[] = $r['club_name']; 
        $chart_members[] = (int)$r['cnt']; 
    } 
}

// Chart 2: Active vs Inactive
$inactive_clubs = $total_clubs - $active_clubs;

// ---- TABLE: Club List with Advisor Names (Fixing the Warning) ----
$top_clubs = [];
$res = mysqli_query($link, "
    SELECT c.clubID, c.club_name, c.club_operational_status,
           COALESCE(adm.admin_name, 'No Advisor') as advisor_name,
           (SELECT COUNT(*) FROM membership m2 WHERE m2.clubID = c.clubID) as member_count,
           (SELECT COUNT(*) FROM membership m3 JOIN membershiprole mr ON m3.roleID = mr.roleID 
            WHERE m3.clubID = c.clubID AND mr.m_role_desc != 'Member') as committee_count
    FROM club c
    LEFT JOIN administrator adm ON c.userID = adm.userID
    ORDER BY member_count DESC
");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $top_clubs[] = $r; } }

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Dashboard - FK Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-header h2 { font-size: 24px; color: #1e293b; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 25px 0; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; border-left: 5px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card.green { border-left-color: #10b981; }
        .stat-card.blue  { border-left-color: #3b82f6; }
        .stat-card.amber { border-left-color: #f59e0b; }
        .stat-card.dark  { border-left-color: #0f172a; }
        .stat-card h3 { font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
        .stat-card .number { font-size: 32px; font-weight: 800; color: #1e293b; }

        .charts-row { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 20px; }
        .chart-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .chart-title { font-size: 15px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        .canvas-wrap { position: relative; height: 240px; }

        .table-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; text-align: left; padding: 12px 20px; font-size: 12px; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    <div class="content-area">
        <div class="page-header">
            <h2>Club Management Analytics</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card dark">
                <h3>Total Registered Clubs</h3>
                <div class="number"><?php echo $total_clubs; ?></div>
            </div>
            <div class="stat-card green">
                <h3>Operational Clubs</h3>
                <div class="number"><?php echo $active_clubs; ?></div>
            </div>
            <div class="stat-card blue">
                <h3>Unique Members</h3>
                <div class="number"><?php echo $total_members; ?></div>
            </div>
            <div class="stat-card amber">
                <h3>Committee Roles</h3>
                <div class="number"><?php echo $total_committees; ?></div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-title">Membership Size by Club</div>
                <div class="canvas-wrap"><canvas id="barChart"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-title">Club Operational Status</div>
                <div class="canvas-wrap"><canvas id="pieChart"></canvas></div>
            </div>
        </div>

        <div class="table-card">
            <div class="chart-title" style="padding: 20px; border:none;">Club Overview List</div>
            <table>
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Advisor</th>
                        <th>Total Members</th>
                        <th>Committee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_clubs as $club): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($club['club_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($club['advisor_name']); ?></td>
                        <td><?php echo $club['member_count']; ?> students</td>
                        <td><?php echo $club['committee_count']; ?> positions</td>
                        <td>
                            <span class="status-badge <?php echo $club['club_operational_status']=='Active'?'status-active':'status-inactive'; ?>">
                                <?php echo $club['club_operational_status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Bar Chart
    new Chart(document.getElementById('barChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_clubs); ?>,
            datasets: [{ label: 'Members', data: <?php echo json_encode($chart_members); ?>, backgroundColor: '#3b82f6', borderRadius: 5 }]
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: ['Active', 'Inactive'],
            datasets: [{ data: [<?php echo $active_clubs; ?>, <?php echo $inactive_clubs; ?>], backgroundColor: ['#10b981', '#f43f5e'] }]
        },
        options: { maintainAspectRatio: false }
    });
});
</script>
</body>
</html>