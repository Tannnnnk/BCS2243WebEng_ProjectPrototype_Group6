<?php
session_start();

// Redirect to login if not an Administrator
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

// Fetch Admin's Real Name and Photo for the Top Bar
$admin_name = $username; 
$photo_path = "";
try {
    $sql_admin = "SELECT admin_name, admin_photo FROM administrator WHERE userID = '$userID'";
    $res_admin = mysqli_query($link, $sql_admin);
    if ($res_admin && $row = mysqli_fetch_assoc($res_admin)) {
        $admin_name = !empty($row['admin_name']) ? $row['admin_name'] : $username;
        $photo_path = !empty($row['admin_photo']) ? $row['admin_photo'] : "";
    }
} catch (Exception $e) {}

// ==========================================
// HANDLE FORM SUBMISSIONS (ADD, EDIT, DELETE)
// ==========================================
$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ACTION: ADD EVENT
    if (isset($_POST['action']) && $_POST['action'] == 'add_event') {
        $event_id = mysqli_real_escape_string($link, $_POST['event_id']);
        $event_title = mysqli_real_escape_string($link, $_POST['event_title']);
        $event_desc = mysqli_real_escape_string($link, $_POST['event_desc']);
        $event_date = mysqli_real_escape_string($link, $_POST['event_date']);
        $event_time = mysqli_real_escape_string($link, $_POST['event_time']);
        $event_venue = mysqli_real_escape_string($link, $_POST['event_venue']);
        $event_max = (int) $_POST['event_max'];
        
        $check = mysqli_query($link, "SELECT * FROM events WHERE eventID = '$event_id'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "Error: Event ID already exists!";
            $msg_type = "error";
        } else {
            // ==========================================
            // AUTOMATIC QR CODE GENERATION
            // ==========================================
            require_once '../phpqrcode/qrlib.php'; // Load the library
            
            // 1. Ensure the qrcodes folder exists
            $storage_folder = "qrcodes/";
            if (!is_dir($storage_folder)) {
                mkdir($storage_folder, 0777, true);
            }
            
            // 2. Define File Name and Path
            $file_name = $event_id . "_qr.png";
            $attendance_qr = $storage_folder . $file_name;
            
            // 3. Define the Data inside the QR Code (The link to your attendance form)
            // Note: Change 'localhost' to your real domain name when you host this online
            $qr_data = "http://10.62.122.105/webeproject/attendance_form.php?event=" . $event_id;
            
            // 4. Generate and save the QR Code image
            QRcode::png($qr_data, $attendance_qr, QR_ECLEVEL_L, 10, 2);
            // ==========================================

            $sql_insert = "INSERT INTO events (eventID, event_title, event_desc, event_date, event_time, event_venue, event_max_participants, attendance_qr) 
                           VALUES ('$event_id', '$event_title', '$event_desc', '$event_date', '$event_time', '$event_venue', $event_max, '$attendance_qr')";
            
            if (mysqli_query($link, $sql_insert)) {
                $msg = "Success: Event created and QR Code auto-generated successfully!";
                $msg_type = "success";
            } else {
                $msg = "Error: Could not create event.";
                $msg_type = "error";
            }
        }
    }
    
    // ACTION: EDIT EVENT
    if (isset($_POST['action']) && $_POST['action'] == 'edit_event') {
        $event_id = mysqli_real_escape_string($link, $_POST['edit_event_id']);
        $event_title = mysqli_real_escape_string($link, $_POST['edit_event_title']);
        $event_desc = mysqli_real_escape_string($link, $_POST['edit_event_desc']);
        $event_date = mysqli_real_escape_string($link, $_POST['edit_event_date']);
        $event_time = mysqli_real_escape_string($link, $_POST['edit_event_time']);
        $event_venue = mysqli_real_escape_string($link, $_POST['edit_event_venue']);
        $event_max = (int) $_POST['edit_event_max'];
        
        $sql_update = "UPDATE events SET 
                        event_title = '$event_title', 
                        event_desc = '$event_desc', 
                        event_date = '$event_date', 
                        event_time = '$event_time', 
                        event_venue = '$event_venue', 
                        event_max_participants = $event_max 
                       WHERE eventID = '$event_id'";
                       
        if (mysqli_query($link, $sql_update)) {
            $msg = "Success: Event updated successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error: Could not update event.";
            $msg_type = "error";
        }
    }
    
    // ACTION: DELETE EVENT
    if (isset($_POST['action']) && $_POST['action'] == 'delete_event') {
        $del_id = mysqli_real_escape_string($link, $_POST['del_id']);
        
        // Optional cleanup: Delete the physical QR code image file
        $check_qr = mysqli_query($link, "SELECT attendance_qr FROM events WHERE eventID = '$del_id'");
        if ($row = mysqli_fetch_assoc($check_qr)) {
            if (!empty($row['attendance_qr']) && file_exists($row['attendance_qr'])) {
                unlink($row['attendance_qr']); // Deletes the image from the folder
            }
        }
        
        if (mysqli_query($link, "DELETE FROM events WHERE eventID = '$del_id'")) {
            $msg = "Success: Event permanently deleted.";
            $msg_type = "success";
        } else {
            $msg = "Error: Could not delete event (Check foreign key constraints).";
            $msg_type = "error";
        }
    }
}

// Fetch All Events
$all_events = [];
$res_events = mysqli_query($link, "SELECT * FROM events ORDER BY event_date DESC, event_time DESC");
if ($res_events) { 
    while ($row = mysqli_fetch_assoc($res_events)) { 
        $all_events[] = $row; 
    } 
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - FK Management System</title>
    <style>
        /* CONTENT AREA */
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; margin-top: 20px; }
        .header-section:first-of-type { margin-top: 0; }
        .header-section h2 { font-size: 24px; color: #1a202c; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;}
        .btn-primary { background-color: #10b981; color: white; }
        .btn-primary:hover { background-color: #059669; }
        .btn-warning { background-color: #f59e0b; color: white; }
        .btn-warning:hover { background-color: #d97706; }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-info { background-color: #3b82f6; color: white; }
        .btn-info:hover { background-color: #2563eb; }

        /* ALERT MESSAGES */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

        /* FORMS LAYOUT */
        .form-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; display: none; }
        .form-card.add-border { border-top: 4px solid #10b981; }
        .form-card.edit-border { border-top: 4px solid #f59e0b; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #4a5568; font-weight: bold; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; background-color: #fff; }
        .form-group input:disabled, .form-group input[readonly] { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #10b981; }
        .full-width { grid-column: 1 / -1; }

        /* DATA TABLE */
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #4a5568; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 16px 20px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 20px; border-bottom: 1px solid #e2e8f0; color: #1a202c; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: #f1f5f9; }
        
        .action-buttons { display: flex; gap: 8px; justify-content: flex-end; }
    </style>
</head>
<body>
    
    <?php include 'administrator_background.php'; ?>
    
        <div class="content-area">
            
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="header-section">
                <div>
                    <h2>Manage Events</h2>
                    <p style="color: #718096; margin-top: 3px;">Create new events, edit details, and manage attendance QR codes.</p>
                </div>
                <button class="btn btn-primary" onclick="toggleForm('addEventForm')">+ Add New Event</button>
            </div>

            <div class="form-card add-border" id="addEventForm">
                <h3 style="margin-bottom: 20px; color: #065f46;">Create New Event</h3>
                <form action="manage_events.php" method="POST">
                    <input type="hidden" name="action" value="add_event">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Event ID</label>
                            <input type="text" name="event_id" required placeholder="e.g. E101">
                        </div>
                        <div class="form-group">
                            <label>Event Title</label>
                            <input type="text" name="event_title" required placeholder="e.g. Badminton Championship">
                        </div>
                        <div class="form-group full-width">
                            <label>Event Description</label>
                            <textarea name="event_desc" rows="3" placeholder="Enter event details..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="event_time" required>
                        </div>
                        <div class="form-group">
                            <label>Venue</label>
                            <input type="text" name="event_venue" required placeholder="e.g. Dewan Serbaguna">
                        </div>
                        <div class="form-group">
                            <label>Max Participants</label>
                            <input type="number" name="event_max" required placeholder="e.g. 100">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Save Event & Generate QR</button>
                        <button type="button" class="btn" style="background: #e2e8f0; color: #4a5568;" onclick="toggleForm('addEventForm')">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="form-card edit-border" id="editEventForm">
                <h3 style="margin-bottom: 20px; color: #d97706;">Edit Event Details</h3>
                <form action="manage_events.php" method="POST">
                    <input type="hidden" name="action" value="edit_event">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Event ID (Cannot be changed)</label>
                            <input type="text" id="edit_event_id" name="edit_event_id" readonly>
                        </div>
                        <div class="form-group">
                            <label>Event Title</label>
                            <input type="text" id="edit_event_title" name="edit_event_title" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Event Description</label>
                            <textarea id="edit_event_desc" name="edit_event_desc" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" id="edit_event_date" name="edit_event_date" required>
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" id="edit_event_time" name="edit_event_time" required>
                        </div>
                        <div class="form-group">
                            <label>Venue</label>
                            <input type="text" id="edit_event_venue" name="edit_event_venue" required>
                        </div>
                        <div class="form-group">
                            <label>Max Participants</label>
                            <input type="number" id="edit_event_max" name="edit_event_max" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Attendance QR File Path (Read-Only)</label>
                            <input type="text" id="edit_attendance_qr" name="edit_attendance_qr" readonly>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-warning">Update Event</button>
                        <button type="button" class="btn" style="background: #e2e8f0; color: #4a5568;" onclick="toggleForm('editEventForm')">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Title</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Max Pax</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_events) > 0): ?>
                            <?php foreach ($all_events as $evt): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($evt['eventID']); ?></strong></td>
                                    <td>
                                        <div style="font-weight: bold;"><?php echo htmlspecialchars($evt['event_title']); ?></div>
                                        <div style="font-size: 12px; color: #718096;"><?php echo htmlspecialchars(substr($evt['event_desc'], 0, 40)) . '...'; ?></div>
                                    </td>
                                    <td style="color: #4a5568;">
                                        <?php echo htmlspecialchars($evt['event_date']); ?><br>
                                        <span style="font-size: 12px; color: #a0aec0;"><?php echo htmlspecialchars($evt['event_time']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($evt['event_venue']); ?></td>
                                    <td><?php echo htmlspecialchars($evt['event_max_participants']); ?></td>
                                    
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!empty($evt['attendance_qr'])): ?>
                                                <a href="<?php echo htmlspecialchars($evt['attendance_qr']); ?>" target="_blank" class="btn btn-info" title="View QR Code">QR</a>
                                            <?php else: ?>
                                                <button class="btn" style="background: #e2e8f0; color: #a0aec0; cursor: not-allowed;" title="No QR Assigned">QR</button>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-warning" onclick="openEditForm(
                                                '<?php echo addslashes($evt['eventID']); ?>',
                                                '<?php echo addslashes($evt['event_title']); ?>',
                                                '<?php echo addslashes($evt['event_desc']); ?>',
                                                '<?php echo addslashes($evt['event_date']); ?>',
                                                '<?php echo addslashes($evt['event_time']); ?>',
                                                '<?php echo addslashes($evt['event_venue']); ?>',
                                                '<?php echo addslashes($evt['event_max_participants']); ?>',
                                                '<?php echo addslashes($evt['attendance_qr']); ?>'
                                            )">Edit</button>

                                            <form action="manage_events.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this event? All associated registrations and attendance records will also be deleted.');">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="del_id" value="<?php echo htmlspecialchars($evt['eventID']); ?>">
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px; color: #718096;">No events found in the database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        // Toggles visibility of forms
        function toggleForm(formId) {
            var targetForm = document.getElementById(formId);
            var alternateFormId = (formId === 'addEventForm') ? 'editEventForm' : 'addEventForm';
            var alternateForm = document.getElementById(alternateFormId);
            
            if (alternateForm) alternateForm.style.display = "none";
            
            if (targetForm.style.display === "block") {
                targetForm.style.display = "none";
            } else {
                targetForm.style.display = "block";
            }
        }

        // Populates and opens the Edit Form
        function openEditForm(id, title, desc, date, time, venue, max, qr) {
            document.getElementById('edit_event_id').value = id;
            document.getElementById('edit_event_title').value = title;
            document.getElementById('edit_event_desc').value = desc;
            document.getElementById('edit_event_date').value = date;
            document.getElementById('edit_event_time').value = time;
            document.getElementById('edit_event_venue').value = venue;
            document.getElementById('edit_event_max').value = max;
            document.getElementById('edit_attendance_qr').value = qr;
            
            document.getElementById('addEventForm').style.display = "none";
            document.getElementById('editEventForm').style.display = "block";
            
            // Scroll to the edit form
            document.getElementById('editEventForm').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>