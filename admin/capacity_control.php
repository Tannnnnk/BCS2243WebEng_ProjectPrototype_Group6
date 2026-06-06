<?php
require_once 'admin_login_materials.php';

// Get Event ID from URL query string
if (!isset($_GET['eventID']) || empty($_GET['eventID'])) {
    header("Location: event_directory.php");
    exit();
}

$eventID = mysqli_real_escape_string($link, $_GET['eventID']);

// Initialize message banners
$msg = "";
$msg_type = "";

// ==========================================
// HANDLE CAPACITY OPERATIONS (POST ACTIONS)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ACTION 1: INCREASE CAPACITY
    if (isset($_POST['action']) && $_POST['action'] == 'increase_capacity') {
        $increment = (int)$_POST['increment_amount'];
        if ($increment > 0) {
            $update_sql = "UPDATE events SET event_max_participants = event_max_participants + $increment WHERE eventID = '$eventID'";
            if (mysqli_query($link, $update_sql)) {
                $msg = "Success: Maximum event capacity increased by $increment slots.";
                $msg_type = "success";
            } else {
                $msg = "Error: Failed to update capacity limits.";
                $msg_type = "error";
            }
        }
    }

    // ACTION 2: APPROVE STUDENT FROM WAITING LIST
    if (isset($_POST['action']) && $_POST['action'] == 'approve_student') {
        $target_userID = mysqli_real_escape_string($link, $_POST['userID']); 
        
        $approve_sql = "UPDATE eventregistration 
                        SET registration_status = 'Confirmed' 
                        WHERE userID = '$target_userID' AND eventID = '$eventID'";
        
        if (mysqli_query($link, $approve_sql)) {
            $msg = "Success: Student booking request approved and confirmed!";
            $msg_type = "success";
        } else {
            $msg = "Error: " . mysqli_error($link);
            $msg_type = "error";
        }
    }
}

// ==========================================
// FETCH REQUISITE METRICS AND DATA STREAMS
// ==========================================

// 1. Fetch event core data
$event_title = "Unknown Event";
$max_capacity = 0;

$evt_res = mysqli_query($link, "SELECT event_title, event_max_participants FROM events WHERE eventID = '$eventID'");
if ($evt_res && $evt_row = mysqli_fetch_assoc($evt_res)) {
    $event_title = $evt_row['event_title'];
    $max_capacity = (int)$evt_row['event_max_participants'];
}

// 2. Compute Active Registrations
$active_count = 0;
$active_res = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration WHERE eventID = '$eventID' AND registration_status = 'Confirmed'");
if ($active_res && $act_row = mysqli_fetch_assoc($active_res)) {
    $active_count = (int)$act_row['total'];
}

// 3. Compute Waiting List Count
$waiting_count = 0;
$waiting_res = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration WHERE eventID = '$eventID' AND registration_status = 'Waiting'");
if ($waiting_res && $wait_row = mysqli_fetch_assoc($waiting_res)) {
    $waiting_count = (int)$wait_row['total'];
}

// Calculate residual slots
$remaining_slots = $max_capacity - $active_count;
if ($remaining_slots < 0) $remaining_slots = 0;

$fill_percentage = ($max_capacity > 0) ? round(($active_count / $max_capacity) * 100) : 0;
if ($fill_percentage > 100) $fill_percentage = 100;

// 4. Fetch the Waiting list queue records
$waiting_list_records = [];
$queue_sql = "SELECT er.userID, er.registration_date, s.stu_ID, s.stu_name 
              FROM eventregistration er
              JOIN students s ON er.userID = s.userID 
              WHERE er.eventID = '$eventID' AND er.registration_status = 'Waiting'
              ORDER BY er.registration_date ASC";
$queue_res = mysqli_query($link, $queue_sql);
if ($queue_res) {
    while ($row = mysqli_fetch_assoc($queue_res)) {
        $waiting_list_records[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capacity Control - <?php echo htmlspecialchars($eventID); ?></title>
    <style>
        .header-section { margin-bottom: 25px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; font-size: 14px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .metric-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; text-align: center; }
        .metric-card .label { font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 8px; }
        .metric-card .value { font-size: 28px; font-weight: bold; color: #1a202c; margin: 0; }
        .panel-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .panel-title { font-size: 16px; font-weight: bold; color: #2d3748; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        .input-inline-group { display: flex; gap: 10px; margin-top: 15px; }
        .input-field { padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; width: 100%; max-width: 200px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-indigo { background-color: #6366f1; color: white; }
        .btn-success { background-color: #10b981; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #f8fafc; color: #4a5568; font-size: 12px; text-transform: uppercase; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #2d3748; font-size: 13px; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    <div class="content-area">
        <?php if (!empty($msg)): ?>
            <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="header-section">
            <h2>Capacity Control</h2>
            <p>Managing: <strong><?php echo htmlspecialchars($eventID); ?> - <?php echo htmlspecialchars($event_title); ?></strong></p>
        </div>

        <div class="metrics-grid">
            <div class="metric-card"><div class="label">Max Capacity</div><div class="value"><?php echo $max_capacity; ?></div></div>
            <div class="metric-card"><div class="label">Confirmed</div><div class="value"><?php echo $active_count; ?></div></div>
            <div class="metric-card"><div class="label">Remaining</div><div class="value"><?php echo $remaining_slots; ?></div></div>
            <div class="metric-card"><div class="label">Waiting List</div><div class="value"><?php echo $waiting_count; ?></div></div>
        </div>

        <div class="panel-box">
            <h4 class="panel-title">Increase Event Capacity</h4>
            <form method="POST">
                <input type="hidden" name="action" value="increase_capacity">
                <div class="input-inline-group">
                    <input type="number" name="increment_amount" class="input-field" value="10" min="1" required>
                    <button type="submit" class="btn btn-indigo">Increase Capacity</button>
                </div>
            </form>
        </div>

        <div class="panel-box">
            <h4 class="panel-title">Approve Students from Waiting Queue</h4>
            <table>
                <thead><tr><th>Student Details</th><th style="text-align: right;">Action</th></tr></thead>
                <tbody>
                    <?php if (count($waiting_list_records) > 0): ?>
                        <?php foreach ($waiting_list_records as $wait_student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($wait_student['stu_name']); ?></strong></td>
                                <td style="text-align: right;">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="approve_student">
                                        <input type="hidden" name="userID" value="<?php echo htmlspecialchars($wait_student['userID']); ?>">
                                        <button type="submit" class="btn btn-success">Approve</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No students on waiting list.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>