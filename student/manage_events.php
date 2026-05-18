<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Guard Check: Secure context to authenticate logged-in users
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Student';
// Explicitly setting the structural display context to Committee
$role = 'Committee'; 

// --- DB QUERY: FETCH GREETINGS STRINGS ---
$photo_path = "";
$stu_name = $username; 
$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);
if ($result_profile && $row = mysqli_fetch_assoc($result_profile)) {
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
}

$msg = "";
$msg_type = "";

// --- PRIVILEGE GUARD: Verify Committee Status (roleID < 'R08') ---
$check_committee = "SELECT m.* FROM membership m WHERE m.userID = '$userID' AND m.roleID < 'R08' LIMIT 1";
$res_committee = mysqli_query($link, $check_committee);
$is_committee = (mysqli_num_rows($res_committee) > 0);

if (!$is_committee) {
    echo "<script>alert('Access Denied: Committee privileges required.'); window.location.href='event_directory.php';</script>";
    exit();
}

// --- ACTION PROCESSOR: UPDATE SPECIFIC EVENT RECORD ---
if (isset($_POST['update_event'])) {
    $edit_id                = mysqli_real_escape_string($link, $_POST['eventID']);
    $title                  = mysqli_real_escape_string($link, $_POST['event_title']);
    $desc                   = mysqli_real_escape_string($link, $_POST['event_desc']);
    $date                   = mysqli_real_escape_string($link, $_POST['event_date']);
    $time                   = mysqli_real_escape_string($link, $_POST['event_time']);
    $venue                  = mysqli_real_escape_string($link, $_POST['event_venue']);
    $event_max_participants = intval($_POST['event_max_participants']);
    
    // Note: If you eventually want to save the changed clubID directly into the events table,
    // you would add `clubID = '$chosen_clubID'` here once your database column structure supports it.
    $update_query = "UPDATE events SET 
                        event_title = '$title', 
                        event_desc = '$desc', 
                        event_venue = '$venue', 
                        event_date = '$date', 
                        event_time = '$time',
                        event_max_participants = $event_max_participants 
                     WHERE eventID = '$edit_id'";

    if (mysqli_query($link, $update_query)) {
        $msg = "🎉 Event Record Updated Successfully!";
        $msg_type = "success";
    } else {
        $msg = "❌ Error Updating Record: " . mysqli_error($link);
        $msg_type = "error";
    }
}

// --- ACTION PROCESSOR: DELETE SPECIFIC EVENT RECORD ---
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($link, $_GET['delete_id']);
    if (mysqli_query($link, "DELETE FROM events WHERE eventID = '$del_id'")) {
        $msg = "✅ Event record successfully removed.";
        $msg_type = "success";
    } else {
        $msg = "❌ Error deleting event track record.";
        $msg_type = "error";
    }
}

// --- INITIALIZE SEARCH & FILTERS ---
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$filter_venue = isset($_GET['venue']) ? mysqli_real_escape_string($link, $_GET['venue']) : '';

// --- BUILD DYNAMIC LOGIC SEARCH QUERY ---
$sql = "SELECT e.*, m.clubID 
        FROM events e
        INNER JOIN membership m ON m.userID = '$userID'
        WHERE 1=1";

if (!empty($search_keyword)) {
    $sql .= " AND (e.event_title LIKE '%$search_keyword%' OR e.event_desc LIKE '%$search_keyword%')";
}
if (!empty($filter_venue)) {
    $sql .= " AND e.event_venue = '$filter_venue'";
}
$sql .= " ORDER BY e.event_date ASC, e.event_time ASC"; 
$result_events = mysqli_query($link, $sql);

// --- FETCH INDIVIDUAL RECORD IF EDIT TRIGGERED VIA GET ---
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = mysqli_real_escape_string($link, $_GET['edit_id']);
    
    $res_edit = mysqli_query($link, "SELECT e.*, m.clubID 
                                     FROM events e 
                                     INNER JOIN membership m ON m.userID = '$userID' 
                                     WHERE e.eventID = '$edit_id'");
    if ($res_edit && mysqli_num_rows($res_edit) > 0) {
        $edit_data = mysqli_fetch_assoc($res_edit);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events Portal</title>
    <style>
        .workspace-wrapper { display: flex; flex-direction: column; gap: 20px; width: 100%; }
        
        .alert { padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .role-indicator { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; background-color: #fef3c7; color: #d97706; }

        .filter-bar-row { display: flex; gap: 15px; margin-bottom: 25px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .filter-bar-row form { display: flex; gap: 15px; width: 100%; align-items: center; }
        .search-group { flex: 2; }
        .dropdown-group { flex: 1; }
        .filter-control { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #334155; box-sizing: border-box; }
        .btn-filter { background-color: #475569; color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: bold; text-decoration: none; border: none; cursor: pointer; }
        .btn-clear { background-color: #e2e8f0; color: #475569; padding: 8px 16px; border-radius: 6px; font-size: 13px; text-decoration: none; text-align: center; }

        table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 20px; }
        th { background-color: #f8fafc; padding: 12px; font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        .status-badge { font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 10px; text-transform: uppercase; display: inline-block; }
        .status-upcoming { background-color: #dbeafe; color: #1e40af; }
        .status-past { background-color: #f1f5f9; color: #475569; }

        .action-row-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        .left-actions { display: flex; gap: 10px; }
        
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; display: inline-block; text-align: center; }
        .btn-view { background-color: #0ea5e9; color: white; }
        .btn-edit { background-color: #f59e0b; color: white; }
        .btn-delete { background-color: #ef4444; color: white; }
        .btn-list { background-color: #10b981; color: white; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .modal-card { background: white; border-radius: 12px; max-width: 600px; width: 100%; padding: 25px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 15px; }
        .modal-form-group { display: flex; flex-direction: column; gap: 5px; }
        .modal-form-group label { font-size: 12px; font-weight: bold; color: #475569; text-transform: uppercase; }
        .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="workspace-wrapper">
            
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="central-board">
                <div class="board-title">
                    <span>📝 Manage Events Workspace</span>
                    <span class="role-indicator">Role: <?php echo htmlspecialchars($role); ?></span>
                </div>

                <div class="filter-bar-row">
                    <form method="GET" action="">
                        <div class="search-group">
                            <input type="text" name="search" class="filter-control" placeholder="🔍 Search Event Title or Details..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                        </div>
                        <div class="dropdown-group">
                            <select name="venue" class="filter-control">
                                <option value="">-- All Venues Filter --</option>
                                <option value="Dewan Serbaguna, UMPSA Pekan" <?php if($filter_venue=='Dewan Serbaguna, UMPSA Pekan') echo 'selected'; ?>>Dewan Serbaguna, UMPSA Pekan</option>
                                <option value="Main Auditorium" <?php if($filter_venue=='Main Auditorium') echo 'selected'; ?>>Main Auditorium</option>
                                <option value="Block W Lab 2" <?php if($filter_venue=='Block W Lab 2') echo 'selected'; ?>>Block W Computer Lab 2</option>
                                <option value="Student Lounge" <?php if($filter_venue=='Student Lounge') echo 'selected'; ?>>Student Activity Center</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-filter">Apply Filters</button>
                        <a href="manage_events.php" class="btn-clear">Reset</a>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>EventID</th>
                            <th>Event Title</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Action Selection</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_events && mysqli_num_rows($result_events) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_events)): 
                                $is_past = (strtotime($row['event_date']) < strtotime(date('Y-m-d')));
                                $status_text = $is_past ? "Past Event" : "Upcoming";
                                $status_class = $is_past ? "status-past" : "status-upcoming";
                            ?>
                                <tr>
                                    <td><code>#EV-<?php echo $row['eventID']; ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
                                    <td>📅 <?php echo date('d M Y', strtotime($row['event_date'])); ?><br><small>🕒 <?php echo date('h:i A', strtotime($row['event_time'])); ?></small></td>
                                    <td>📍 <?php echo htmlspecialchars($row['event_venue']); ?></td>
                                    <td>👥 <?php echo $row['event_max_participants']; ?> slots</td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    <td>
                                        <input type="radio" name="selected_event_tracker" value="<?php echo $row['eventID']; ?>" onclick="updateActionLinks('<?php echo $row['eventID']; ?>', '<?php echo $row['clubID']; ?>')"> Select
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; color:#94a3b8;">No records matched standard query parameters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="action-row-footer">
                    <div class="left-actions">
                        <a href="#" id="wireframe_btn_view" class="btn btn-view" onclick="alert('Please select an event row radio tracker first.')">👁️ View Details</a>
                        <a href="#" id="wireframe_btn_edit" class="btn btn-edit" onclick="alert('Please select an event row radio tracker first.')">✏️ Edit</a>
                        <a href="#" id="wireframe_btn_delete" class="btn btn-delete" onclick="alert('Please select an event row radio tracker first.')">🗑️ Delete</a>
                    </div>
                    <div>
                        <a href="#" id="wireframe_btn_list" class="btn btn-list" onclick="alert('Please select an event row radio tracker first.')">📋 Participants List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($edit_data): ?>
        <div class="modal-overlay">
            <div class="modal-card">
                <div style="font-size: 16px; font-weight: bold; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">✏️ Modify Event Record (#EV-<?php echo $edit_data['eventID']; ?>)</div>
                <form method="POST" action="manage_events.php">
                    <input type="hidden" name="eventID" value="<?php echo $edit_data['eventID']; ?>">
                    
                    <div class="modal-form-group">
                        <label>Assigned Club ID</label>
                        <select name="clubID" class="filter-control" required>
                            <?php 
                            
                            $club_query = "SELECT clubID FROM club";
                            $club_result = mysqli_query($link, $club_query);
                            if ($club_result && mysqli_num_rows($club_result) > 0) {
                                while($club_row = mysqli_fetch_assoc($club_result)) {
                                    $selected = ($club_row['clubID'] == $edit_data['clubID']) ? 'selected' : '';
                                    echo "<option value='".htmlspecialchars($club_row['clubID'])."' $selected>".htmlspecialchars($club_row['clubID'])."</option>";
                                }
                            } else {
                                echo "<option value='".htmlspecialchars($edit_data['clubID'])."'>".htmlspecialchars($edit_data['clubID'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="modal-form-group" style="margin-top: 10px;">
                        <label>Event Title</label>
                        <input type="text" name="event_title" class="filter-control" value="<?php echo htmlspecialchars($edit_data['event_title']); ?>" required>
                    </div>
                    
                    <div class="modal-form-group" style="margin-top: 10px;">
                        <label>Description Details</label>
                        <textarea name="event_desc" class="filter-control" style="height: 80px;" required><?php echo htmlspecialchars($edit_data['event_desc']); ?></textarea>
                    </div>

                    <div class="modal-form-group" style="margin-top: 10px;">
                        <label>Scheduled Date & Time</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="date" name="event_date" class="filter-control" style="flex: 1;" value="<?php echo $edit_data['event_date']; ?>" required>
                            <input type="time" name="event_time" class="filter-control" style="flex: 1;" value="<?php echo $edit_data['event_time']; ?>" required>
                        </div>
                    </div>

                    <div class="modal-form-group" style="margin-top: 10px;">
                        <label>Venue</label>
                        <select name="event_venue" class="filter-control" required>
                            <option value="Dewan Serbaguna, UMPSA Pekan" <?php if($edit_data['event_venue']=='Dewan Serbaguna, UMPSA Pekan') echo 'selected'; ?>>Dewan Serbaguna, UMPSA Pekan</option>
                            <option value="Main Auditorium" <?php if($edit_data['event_venue']=='Main Auditorium') echo 'selected'; ?>>Main Auditorium</option>
                            <option value="Block W Lab 2" <?php if($edit_data['event_venue']=='Block W Lab 2') echo 'selected'; ?>>Block W Computer Lab 2</option>
                            <option value="Student Lounge" <?php if($edit_data['event_venue']=='Student Lounge') echo 'selected'; ?>>Student Activity Center</option>
                        </select>
                    </div>

                    <div class="modal-form-group" style="margin-top: 10px;">
                        <label>Maximum Capacity Limit</label>
                        <input type="number" name="event_max_participants" class="filter-control" value="<?php echo $edit_data['event_max_participants']; ?>" min="1" required>
                    </div>

                    <div class="modal-buttons">
                        <a href="manage_events.php" class="btn btn-clear" style="padding: 8px 16px;">Discard Changes</a>
                        <button type="submit" name="update_event" class="btn btn-list" style="padding: 8px 16px;">Save Modification</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function updateActionLinks(eventId, clubId) {
            // View Action
            var viewBtn = document.getElementById('wireframe_btn_view');
            viewBtn.onclick = null; 
            viewBtn.href = "view_event_details.php?id=" + eventId;
            
            // Edit Action
            var editBtn = document.getElementById('wireframe_btn_edit');
            editBtn.onclick = null;
            editBtn.href = "manage_events.php?edit_id=" + eventId;
            
            // Delete Action
            var delBtn = document.getElementById('wireframe_btn_delete');
            delBtn.onclick = function() {
                return confirm('Are you sure you want to permanently delete this event track record?');
            };
            delBtn.href = "manage_events.php?delete_id=" + eventId;

            
            var listBtn = document.getElementById('wireframe_btn_list');
            listBtn.onclick = null;
            listBtn.href = "manage_attendance.php?eventID=" + eventId + "&clubID=" + clubId;
        }
    </script>
</body>
</html>