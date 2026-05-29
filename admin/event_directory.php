<?php
require_once 'admin_login_materials.php';

$msg = "";
$msg_type = "";

// 1. Get club id from url parameter, fallback to database if missing
$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';

if (empty($clubID)) {
    $fallback = mysqli_query($link, "SELECT clubID FROM club LIMIT 1");
    if ($fallback && $f_row = mysqli_fetch_assoc($fallback)) {
        $clubID = $f_row['clubID'];
    }
}

// 2. Database metrics collection (Only keeping what is actually displayed)
$my_reg_count = 0;
$q3 = mysqli_query($link, "SELECT COUNT(*) as total FROM events");
if ($q3) { $my_reg_count = mysqli_fetch_assoc($q3)['total']; }

$upcoming_count = 0;
$q4 = mysqli_query($link, "SELECT COUNT(*) as total FROM events WHERE event_date > CURDATE() OR (event_date = CURDATE() AND event_time >= CURTIME())");
if ($q4) { $upcoming_count = mysqli_fetch_assoc($q4)['total']; }

$total_points = 0;
$q5 = mysqli_query($link, "SELECT SUM(point_value) as points FROM points");
if ($q5 && $p_row = mysqli_fetch_assoc($q5)) {
    $total_points = $p_row['points'] ? $p_row['points'] : 0;
}

// 3. General data pulling queries
$events_result = mysqli_query($link, "SELECT * FROM events ORDER BY event_date DESC");
$rec_result = mysqli_query($link, "SELECT * FROM events WHERE event_date > CURDATE() OR (event_date = CURDATE() AND event_time >= CURTIME()) ORDER BY event_date ASC LIMIT 3");
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
        
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th { background-color: #f8fafc; padding: 12px; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        .btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; display: inline-block; }
        .btn-register { background-color: #3b82f6; color: white; }
        .btn-register:hover { background-color: #2563eb; }
        
        .footer-split { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 5px; }
        @media(max-width: 768px) { .footer-split { grid-template-columns: 1fr; } }
        
        .split-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .split-card h3 { font-size: 14px; color: #64748b; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px; }
        .stat-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #475569; }
        .stat-item:last-child { border-bottom: none; }
        .stat-item span { font-weight: bold; color: #1e293b; font-size: 16px; }
        
        .rec-item { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .rec-item:last-child { border-bottom: none; }
        .rec-title { font-weight: bold; color: #1e293b; font-size: 14px; }
        .rec-meta { font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    <div class="content-area">
        <div class="workspace-wrapper">
            
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>
            
            <div class="central-board">
                <div class="board-title">
                    <span>📅 Event Dashboard Portal</span>
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
                                <?php while ($row = mysqli_fetch_assoc($events_result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
                                        <td>📍 <?php echo htmlspecialchars($row['event_venue']); ?></td>
                                        <td>📅 <?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                        <td>
                                            <a href="engagement_trend.php?clubID=<?php echo urlencode($clubID); ?>&eventID=<?php echo urlencode($row['eventID']); ?>" class="btn btn-register">Engagement Trends</a>
                                            <a href="capacity_control.php?eventID=<?php echo urlencode($row['eventID']); ?>" class="btn" style="background-color: #6366f1; color: white;" title="Manage Capacity">Capacity</a>
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
            
        </div>
    </div>
</body>
</html>