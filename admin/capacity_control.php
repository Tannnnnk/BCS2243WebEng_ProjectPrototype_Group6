<?php
session_start();

// Redirect to login if not an Administrator
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

// Adjusted path to look one level up into the root folder
require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

// Get Event ID from URL query string
if (!isset($_GET['eventID']) || empty($_GET['eventID'])) {
    header("Location: manage_events.php");
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
        $target_attendanceID = mysqli_real_escape_string($link, $_POST['attendanceID']);
        
        // Change attendance_status to 'Present' to confirm their booking spot
        $approve_sql = "UPDATE attendance SET attendance_status = 'Present' WHERE attendanceID = '$target_attendanceID' AND eventID = '$eventID'";
        if (mysqli_query($link, $approve_sql)) {
            $msg = "Success: Student booking request approved and confirmed!";
            $msg_type = "success";
        } else {
            $msg = "Error: Failed to process approval.";
            $msg_type = "error";
        }
    }
}

// ==========================================
// FETCH REQUISITE METRICS AND DATA STREAMS
// ==========================================

// 1. Fetch event core data limits
$event_title = "Unknown Event";
$max_capacity = 0;

$evt_res = mysqli_query($link, "SELECT event_title, event_max_participants FROM events WHERE eventID = '$eventID'");
if ($evt_res && $evt_row = mysqli_fetch_assoc($evt_res)) {
    $event_title = $evt_row['event_title'];
    $max_capacity = (int)$evt_row['event_max_participants'];
}

// 2. Compute Active Registrations (Status: 'Present')
$active_count = 0;
$active_res = mysqli_query($link, "SELECT COUNT(*) as total FROM attendance WHERE eventID = '$eventID' AND attendance_status = 'Present'");
if ($active_res && $act_row = mysqli_fetch_assoc($active_res)) {
    $active_count = (int)$act_row['total'];
}

// 3. Compute Waiting List Count (Status: 'Absent' acting as temporary wait state)
$waiting_count = 0;
$waiting_res = mysqli_query($link, "SELECT COUNT(*) as total FROM attendance WHERE eventID = '$eventID' AND attendance_status = 'Absent'");
if ($waiting_res && $wait_row = mysqli_fetch_assoc($waiting_res)) {
    $waiting_count = (int)$wait_row['total'];
}

// Calculate residual slots safely
$remaining_slots = $max_capacity - $active_count;
if ($remaining_slots < 0) $remaining_slots = 0;

// Percentage tracking calculations for visualization bar
$fill_percentage = ($max_capacity > 0) ? round(($active_count / $max_capacity) * 100) : 0;
if ($fill_percentage > 100) $fill_percentage = 100;

// 4. Fetch the Waiting list queue records matching your students schema explicitly
$waiting_list_records = [];
$queue_sql = "SELECT a.attendanceID, a.attendance_date, a.attendance_time, s.stu_ID, s.stu_name, s.stu_email 
              FROM attendance a
              JOIN students s ON a.userID = s.userID 
              WHERE a.eventID = '$eventID' AND a.attendance_status = 'Absent'
              ORDER BY a.attendance_date ASC, a.attendance_time ASC";
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
        /* CANVAS LAYOUT ARRANGEMENTS */
        .header-section { margin-bottom: 25px; }
        .header-section h2 { font-size: 26px; color: #1a202c; margin: 0; }
        .header-section p { color: #718096; margin: 5px 0 0 0; font-size: 14px; }
        
        /* ALERTS AND DIALOGUES */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; font-size: 14px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

        /* KPI DISPLAY GRID CARDS */
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .metric-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; text-align: center; }
        .metric-card .label { font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 8px; }
        .metric-card .value { font-size: 28px; font-weight: bold; color: #1a202c; margin: 0; }
        .metric-card.accent { border-top: 4px solid #6366f1; }

        /* MIDDLE WORKSPACE ROW (CHART VS LIVE STATUS MESSAGES) */
        .workspace-split { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
        .panel-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .panel-title { font-size: 16px; font-weight: bold; color: #2d3748; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }

        /* PROGRESS BAR GRAPHICS */
        .progress-container { margin: 20px 0; }
        .progress-track { background-color: #e2e8f0; height: 24px; border-radius: 12px; overflow: hidden; position: relative; }
        .progress-bar { background: linear-gradient(90deg, #6366f1, #4f46e5); height: 100%; transition: width 0.5s ease-in-out; }
        .progress-text { position: absolute; width: 100%; text-align: center; top: 2px; font-size: 13px; font-weight: bold; color: #1a202c; z-index: 2; }

        /* STATUS DISPLAY ALIGNMENTS */
        .status-pill { display: inline-block; padding: 10px 20px; border-radius: 30px; font-weight: bold; font-size: 14px; margin-bottom: 15px; }
        .status-pill.full { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status-pill.available { background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }

        /* CONTROL INTERFACES AND TABLES */
        .action-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .input-inline-group { display: flex; gap: 10px; margin-top: 15px; }
        .input-field { padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; width: 100%; max-width: 200px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-indigo { background-color: #6366f1; color: white; }
        .btn-indigo:hover { background-color: #4f46e5; }
        .btn-success { background-color: #10b981; color: white; }
        .btn-success:hover { background-color: #059669; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #f8fafc; color: #4a5568; font-size: 12px; text-transform: uppercase; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #2d3748; font-size: 13px; vertical-align: middle; }
    </style>
</head>
<body>

    <?php include 'administrator_background.php'; ?>
    
    <div class="content-area">
        
        <?php if (!empty($msg)): ?>
            <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="header-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2>Capacity Control</h2>
                    <p>Monitoring Allocation and Waiting Lists for: <strong><?php echo htmlspecialchars($eventID); ?> - <?php echo htmlspecialchars($event_title); ?></strong></p>
                </div>
                <a href="manage_events.php" class="btn" style="background-color: #e2e8f0; color: #4a5568;">&larr; Back to Events</a>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">Maximum Capacity</div>
                <div class="value"><?php echo $max_capacity; ?></div>
            </div>
            <div class="metric-card">
                <div class="label">Current Participants</div>
                <div class="value" style="color: #6366f1;"><?php echo $active_count; ?></div>
            </div>
            <div class="metric-card">
                <div class="label">Remaining Slots</div>
                <div class="value" style="color: #10b981;"><?php echo $remaining_slots; ?></div>
            </div>
            <div class="metric-card accent">
                <div class="label">Waiting List</div>
                <div class="value" style="color: #f59e0b;"><?php echo $waiting_count; ?></div>
            </div>
        </div>

        <div class="workspace-split">
            
            <div class="panel-box">
                <h4 class="panel-title">Capacity Progress Chart</h4>
                <p style="font-size: 13px; color: #718096; margin-bottom: 10px;">Visual representation of filled seats vs maximum thresholds.</p>
                
                <div class="progress-container">
                    <div class="progress-track">
                        <div class="progress-bar" style="width: <?php echo $fill_percentage; ?>%;"></div>
                        <div class="progress-text"><?php echo $fill_percentage; ?>% Filled</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #a0aec0; font-weight: bold;">
                    <span>0 Registered</span>
                    <span>Max: <?php echo $max_capacity; ?></span>
                </div>
            </div>

            <div class="panel-box" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                <h4 class="panel-title" style="width: 100%; text-align: left;">Live Booking Directives</h4>
                
                <?php if ($remaining_slots <= 0): ?>
                    <div class="status-pill full">🚨 Event Fully Booked</div>
                    <p style="color: #4a5568; font-size: 14px; margin: 0 10px;">
                        New registrants will automatically be designated as **Absent/Waiting List** status until capacity thresholds are expanded.
                    </p>
                <?php else: ?>
                    <div class="status-pill available">✅ Open for Bookings</div>
                    <p style="color: #4a5568; font-size: 14px; margin: 0 10px;">
                        There are currently **<?php echo $remaining_slots; ?> open allocations available** before booking restrictions trigger structural shifting.
                    </p>
                <?php endif; ?>
            </div>

        </div>

        <div class="action-row">
            
            <div class="panel-box">
                <h4 class="panel-title">Increase Event Capacity</h4>
                <p style="font-size: 13px; color: #718096;">Need to adjust room for additional students? Dynamically raise threshold variables here safely.</p>
                
                <form action="capacity_control.php?eventID=<?php echo urlencode($eventID); ?>" method="POST">
                    <input type="hidden" name="action" value="increase_capacity">
                    <div class="input-inline-group">
                        <input type="number" name="increment_amount" class="input-field" value="10" min="1" required>
                        <button type="submit" class="btn btn-indigo">Increase Capacity</button>
                    </div>
                </form>
            </div>

            <div class="panel-box">
                <h4 class="panel-title">Approve Students from Waiting Queue</h4>
                <p style="font-size: 13px; color: #718096; margin-bottom: 15px;">Manually elevate standby applicants directly into verified spaces when slots free up.</p>
                
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($waiting_list_records) > 0): ?>
                                <?php foreach ($waiting_list_records as $wait_student): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($wait_student['stu_name']); ?></strong><br>
                                            <span style="font-size:11px; color:#718096;">Matric ID: <?php echo htmlspecialchars($wait_student['stu_ID']); ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <form action="capacity_control.php?eventID=<?php echo urlencode($eventID); ?>" method="POST" style="margin:0;">
                                                <input type="hidden" name="action" value="approve_student">
                                                <input type="hidden" name="attendanceID" value="<?php echo htmlspecialchars($wait_student['attendanceID']); ?>">
                                                <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size:11px;">Approve Student</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; color: #a0aec0; padding: 20px;">No students currently on standby waiting tracks.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</body>
</html>