<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'student_login_materials.php';

$target_file = __DIR__ . '/../uploads/' . $photo_path;
if (!empty($photo_path) && file_exists($target_file)) {
    $img_src = "../uploads/" . htmlspecialchars($photo_path);
} else {
    $img_src = "../images/default-avatar.png"; 
}

$username_display = '<img src="' . $img_src . '" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; display: inline-block; vertical-align: middle; margin-right: 12px; border: 2px solid #fff;">' . htmlspecialchars($stu_name);

$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';
if (empty($clubID)) {
    $fallback = mysqli_query($link, "SELECT clubID FROM club LIMIT 1");
    if ($fallback && $f_row = mysqli_fetch_assoc($fallback)) {
        $clubID = $f_row['clubID'];
    }
}

$msg = "";
$msg_type = "";



// --- CONTROLLER LOGIC: FILTER PARAMETERS ---
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($link, $_GET['status']) : '';

$query_sql = "SELECT * FROM events WHERE 1=1";

if (!empty($search_keyword)) {
    $query_sql .= " AND (event_title LIKE '%$search_keyword%' OR event_venue LIKE '%$search_keyword%')";
}

if (!empty($filter_status)) {
    $today_str = date('Y-m-d');
    if ($filter_status === 'Upcoming') {
        $query_sql .= " AND event_date >= '$today_str'";
    } elseif ($filter_status === 'Past') {
        $query_sql .= " AND event_date < '$today_str'";
    }
}
$query_sql .= " ORDER BY event_date ASC";
$events_result = mysqli_query($link, $query_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Club Events Dashboard</title>
    <style>
        .workspace-wrapper { display: flex; flex-direction: column; gap: 20px; width: 100%; font-family: system-ui, -apple-system, sans-serif; }
        .alert { padding: 12px 20px; border-radius: 6px; font-weight: 500; font-size: 14px; margin-bottom: 5px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .nav-back-row { margin-bottom: 5px; }
        .link-back { text-decoration: none; color: #2563eb; font-size: 14px; font-weight: 600; }
        .filter-panel-row { display: flex; gap: 12px; margin-bottom: 25px; align-items: center; background-color: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .filter-control { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: white; }
        .btn-search-apply { background-color: #3b82f6; color: white; border: none; padding: 10px 18px; font-weight: bold; border-radius: 6px; cursor: pointer; }
        .btn-reset-clear { background-color: #e2e8f0; color: #475569; text-decoration: none; padding: 10px 14px; font-size: 14px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f1f5f9; padding: 12px; font-size: 12px; text-transform: uppercase; color: #475569; border-bottom: 2px solid #cbd5e1; }
        td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
        .btn { padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; }
        .btn-view { background-color: #3b82f6; color: white; }
        .btn-register { background-color: #10b981; color: white; }
        .status-badge-registered { font-size: 13px; font-weight: bold; color: #059669; background-color: #d1fae5; padding: 6px 14px; border-radius: 6px; border: 1px solid #10b981; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="workspace-wrapper">
            <div class="nav-back-row">
                <a href="event_directory.php?clubID=<?php echo urlencode($clubID); ?>" class="link-back">⬅️ Back to Dashboard System</a>
            </div>
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>
            <div class="central-board">
                <div class="board-title">
                    <span>🔍 Browse Upcoming Campus Events</span>
                </div>
                <form method="GET" action="" class="filter-panel-row">
                    <input type="hidden" name="clubID" value="<?php echo htmlspecialchars($clubID); ?>">
                    <input type="text" name="search" class="filter-control" placeholder="Search Event Title or Venue..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <select name="status" class="filter-control">
                        <option value="">-- Event Status --</option>
                        <option value="Upcoming" <?php if($filter_status == 'Upcoming') echo 'selected'; ?>>Upcoming</option>
                        <option value="Past" <?php if($filter_status == 'Past') echo 'selected'; ?>>Past</option>
                    </select>
                    <button type="submit" class="btn-search-apply">Filter</button>
                    <a href="browse_event.php?clubID=<?php echo urlencode($clubID); ?>" class="btn-reset-clear">Reset</a>
                </form>
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
                                <?php 
                                    $rowEventID = $row['eventID'];
                                    $reg_check = mysqli_query($link, "SELECT * FROM eventregistration WHERE userID = '$userID' AND eventID = '$rowEventID'");
                                    $has_joined = (mysqli_num_rows($reg_check) > 0);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
                                    <td>📍 <?php echo htmlspecialchars($row['event_venue']); ?></td>
                                    <td>📅 <?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
                                    
                                    <td style="white-space: nowrap;">
    <a href="student_view_event_details.php?id=<?php echo urlencode($row['eventID']); ?>" class="btn btn-view">View Details</a>
    
    <?php if ($has_joined): ?>
        <span class="status-badge-registered">Registered</span>
    <?php else: ?>
        <a href="register_confirm.php?eventID=<?php echo urlencode($row['eventID']); ?>" class="btn btn-register">Register</a>
    <?php endif; ?>
</td>

                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px;">No events match your search.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>