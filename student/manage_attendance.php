<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';

// Get the eventID passed from the main dashboard workspace
$eventID = isset($_GET['eventID']) ? mysqli_real_escape_string($link, $_GET['eventID']) : '';

if (empty($eventID)) {
    echo "<script>alert('Please select an event from the workspace first.'); window.location.href='manage_events.php';</script>";
    exit();
}

$userID = $_SESSION['userID'];
$username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Student';
$role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Student';

// --- DB QUERY: FETCH CURRENT STUDENT INFORMATION & PROFILE PICTURE ---
$photo_path = "";
$stu_name = $username; 

$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);

if ($result_profile && $row = mysqli_fetch_assoc($result_profile)) {
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
}

$target_file = __DIR__ . '/../uploads/' . $photo_path;

if (!empty($photo_path) && file_exists($target_file)) {
    $img_src = "../uploads/" . htmlspecialchars($photo_path);
} else {
    // Falls back to your default avatar picture asset if missing
    $img_src = "../images/default-avatar.png"; 
}

$username = '<img src="' . $img_src . '" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; display: inline-block; vertical-align: middle; margin-right: 12px; border: 2px solid #ffffff;">' . htmlspecialchars($stu_name);
// ---------------------------------------------------------------------

// Fetch current Event Title header
$event_title = "Unknown Event";
$event_query = mysqli_query($link, "SELECT event_title FROM events WHERE eventID = '$eventID'");
if ($event_query && $ev = mysqli_fetch_assoc($event_query)) {
    $event_title = $ev['event_title'];
}

$msg = "";
$msg_type = "";

// MULTI-ACTION HANDLER: Processes Bulk Selection Requests directly on your Attendance Records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_commitee']) && is_array($_POST['selected_commitee'])) {
    $selected_users = array_map(function($id) use ($link) {
        return "'" . mysqli_real_escape_string($link, $id) . "'";
    }, $_POST['selected_commitee']);
    
    $user_id_list = implode(',', $selected_users);

    if (isset($_POST['action_approve'])) {
        // Bulk Update selected rows to 'Present' status with current tracking timestamp
        $bulk_update = "UPDATE attendance SET attendance_status = 'Present', attendance_time = NOW() WHERE eventID = '$eventID' AND userID IN ($user_id_list)";
        if (mysqli_query($link, $bulk_update)) {
            $msg = "✅ Selected participant attendance status updated to Present successfully.";
            $msg_type = "success";
        }
    } elseif (isset($_POST['action_remove'])) {
        // Clear active tracking data rows securely
        $bulk_delete = "DELETE FROM attendance WHERE eventID = '$eventID' AND userID IN ($user_id_list)";
        if (mysqli_query($link, $bulk_delete)) {
            $msg = "🗑️ Selected participants successfully removed from the tracking list.";
            $msg_type = "success";
        }
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['selected_commitee']) || !is_array($_POST['selected_commitee']))) {
    $msg = "⚠️ Please select at least one participant row using the checkboxes first.";
    $msg_type = "error";
}

// Connected via INNER JOIN to match your drawing columns: StudentID, StudentName, Attendance Time, Status
$query_participants = "SELECT 
                        a.attendanceID,
                        a.userID, 
                        s.stu_name AS student_real_name, 
                        a.attendance_time,
                        COALESCE(a.attendance_status, 'Not Checked') AS att_status
                       FROM attendance a 
                       INNER JOIN students s ON a.userID = s.userID
                       WHERE a.eventID = '$eventID'";

$result_participants = mysqli_query($link, $query_participants);
$total_participants = $result_participants ? mysqli_num_rows($result_participants) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants List Workspace</title>
    <style>
        .workspace-wrapper { display: flex; flex-direction: column; gap: 20px; width: 100%; font-family: system-ui, sans-serif; }
        .alert { padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; margin-bottom: 15px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        
        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .role-indicator { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; background-color: #e0f2fe; color: #0369a1; }
        
        .info-display-banner { background-color: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 25px; display: flex; flex-direction: column; gap: 8px; }
        .info-label { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 16px; font-weight: bold; color: #1e293b; }

        table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 20px; }
        th { background-color: #f8fafc; padding: 12px; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        .badge { font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 10px; text-transform: uppercase; }
        .badge-approved { background-color: #d1fae5; color: #065f46; }
        .badge-pending { background-color: #fef3c7; color: #d97706; }
        .badge-neutral { background-color: #e2e2e4; color: #475569; }

        .action-row-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        .btn { padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; display: inline-block; }
        .btn-remove { background-color: #ef4444; color: white; }
        .btn-approve { background-color: #10b981; color: white; }
        .btn-back { background-color: #64748b; color: white; }
        
        .chk-box { width: 16px; height: 16px; cursor: pointer; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="workspace-wrapper">
            
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form id="attendance_bulk_form" method="POST" action="manage_attendance.php?eventID=<?php echo urlencode($eventID); ?>" class="central-board">
                <div class="board-title">
                    <span>Participants List Workspace</span>
                    <span class="role-indicator"><?php echo htmlspecialchars($role); ?></span>
                </div>

                <div class="info-display-banner">
                    <div>
                        <span class="info-label">Event Name:</span>
                        <span class="info-value" style="color: #0284c7;"> <?php echo htmlspecialchars($event_title); ?></span>
                    </div>
                    <div>
                        <span class="info-label">Total Participants:</span>
                        <span class="info-value"> <?php echo $total_participants; ?> Registered Attendees</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th width="50px"><input type="checkbox" id="select_all_trigger" class="chk-box" onclick="toggleSelectAll(this)"></th>
                            <th>StudentID</th>
                            <th>StudentName</th>
                            <th>Attendance Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_participants && $total_participants > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_participants)): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_commitee[]" value="<?php echo $row['userID']; ?>" class="chk-box commitee-record-checkbox">
                                    </td>
                                    <td><code><?php echo htmlspecialchars($row['userID']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($row['student_real_name']); ?></strong></td>
                                    <td>
                                        🕒 <?php echo (!empty($row['attendance_time']) && $row['att_status'] === 'Present') ? date('h:i A (d M)', strtotime($row['attendance_time'])) : '<span style="color: #94a3b8; font-style: italic;">Not Logged</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if (strtolower($row['att_status']) == 'present'): ?>
                                            <span class="badge badge-approved">Present</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral">Not Checked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">
                                    No active tracking log rows found inside your database parameters for this Event ID.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="action-row-footer">
                    <a href="manage_events.php" class="btn btn-back">⬅️ Back to Main Workspace</a>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="action_remove" class="btn btn-remove" onclick="return confirm('Are you sure you want to remove the selected entries?')">Remove</button>
                        <button type="submit" name="action_approve" class="btn btn-approve">Approve</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSelectAll(masterCheckbox) {
            var checkboxes = document.getElementsByClassName('commitee-record-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = masterCheckbox.checked;
            }
        }
    </script>
</body>
</html>