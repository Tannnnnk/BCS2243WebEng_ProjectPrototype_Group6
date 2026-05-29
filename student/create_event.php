<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'student_login_materials.php';

$show_success_toast = false; 
$msg = "";
$msg_type = "";

// --- PRIVILEGE GUARD: Verify Committee Status (roleID < 'R08') ---
$check_committee = "SELECT m.* FROM membership m WHERE m.userID = '$userID' AND m.roleID <= 'R08' LIMIT 1";
$res_committee = mysqli_query($link, $check_committee);
$is_committee = (mysqli_num_rows($res_committee) > 0);

if (!$is_committee) {
    echo "<script>alert('Access Denied: Committee privileges required.'); window.location.href='event_directory.php';</script>";
    exit();
}

// --- NEW QUERY: FETCH ALL EXISTING CLUBS FOR THE DROPDOWN ---
$clubs_list = [];
$sql_clubs = "SELECT clubID, club_name FROM club ORDER BY clubID ASC";
$result_clubs = mysqli_query($link, $sql_clubs);
if ($result_clubs) {
    while ($club_row = mysqli_fetch_assoc($result_clubs)) {
        $clubs_list[] = $club_row;
    }
}

// --- PRE-FILL CLUB ID IF AVAILABLE IN URL ---
$url_clubID = isset($_GET['clubID']) ? htmlspecialchars($_GET['clubID']) : '';

// --- ACTION PROCESSOR: INSERT NEW EVENT RECORD ---
if (isset($_POST['submit_event'])) {
    // 1. Get the clubID selected from your dropdown menu
    $clubID = mysqli_real_escape_string($link, $_POST['clubID']);

    // 2. DOUBLE-CHECK VALIDATION: Make sure it exists in the club table before proceeding
    $check_club_query = "SELECT * FROM club WHERE clubID = '$clubID' LIMIT 1";
    $check_club_res = mysqli_query($link, $check_club_query);

    if (mysqli_num_rows($check_club_res) == 0) {
        $msg = "❌ Error: The selected Club ID does not exist in the database.";
        $msg_type = "error";
    } else {
        // 3. Generate a sequential unique Event ID (E101, E102...) automatically
        $id_check_query = mysqli_query($link, "SELECT eventID FROM events ORDER BY eventID DESC LIMIT 1");
        
        if (mysqli_num_rows($id_check_query) > 0) {
            $last_row = mysqli_fetch_assoc($id_check_query);
            $lastID = $last_row['eventID'];
            $number = (int)substr($lastID, 1);
            $nextNumber = $number + 1;
            $eventID = "E" . $nextNumber;
        } else {
            $eventID = "E101"; 
        }

        // 4. Sanitize all form elements (Only keeping what matches your phpMyAdmin table)
        $event_title            = mysqli_real_escape_string($link, $_POST['event_title']);
        $event_desc             = mysqli_real_escape_string($link, $_POST['event_desc']);
        $event_date             = mysqli_real_escape_string($link, $_POST['event_date']);
        $event_time             = mysqli_real_escape_string($link, $_POST['event_time']);
        $event_venue            = mysqli_real_escape_string($link, $_POST['event_venue']);
        $event_max_participants = intval($_POST['event_max_participants']);

        $check = mysqli_query($link, "SELECT * FROM events WHERE eventID = '$eventID'");
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
            $file_name = $eventID . "_qr.png";
            $attendance_qr = $storage_folder . $file_name;
            
            // 3. Define the Data inside the QR Code (The link to your attendance form)
            // Note: Change 'localhost' to your real domain name when you host this online
            $qr_data = "http://localhost/BCS2243WebEng_ProjectPrototype_Group6/attendance_form.php?eventID=" . $eventID;
            
            // 4. Generate and save the QR Code image
            QRcode::png($qr_data, $attendance_qr, QR_ECLEVEL_L, 10, 2);
            // ==========================================

        // 5. PERFECT MATCH INSERT: Strictly uses the 7 columns visible in your database screenshot
        $sql_insert = "INSERT INTO events (eventID, event_title, event_desc, event_date, event_time, event_venue, event_max_participants, attendance_qr) 
                       VALUES ('$eventID', '$event_title', '$event_desc', '$event_date', '$event_time', '$event_venue', $event_max_participants, '$attendance_qr')";
            
        if (mysqli_query($link, $sql_insert)) {
            $get_member = mysqli_query($link, "
        SELECT memberID 
        FROM membership 
        WHERE userID = '$userID' AND clubID = '$clubID' 
        LIMIT 1
    ");

    if ($get_member && mysqli_num_rows($get_member) > 0) {
        $member_row = mysqli_fetch_assoc($get_member);
        $memberID = $member_row['memberID'];

        // --- NEW: AUTO-INCREMENT COMMITTEE ID ---
        // Fetch the highest numeric committeeID currently in the database
        $max_comm_query = mysqli_query($link, "
            SELECT committeeID 
            FROM committee 
            WHERE committeeID LIKE 'COM%' 
            ORDER BY committeeID DESC 
            LIMIT 1
        ");

        $next_comm_number = 1; // Default fallback if table is empty

        if ($max_comm_query && mysqli_num_rows($max_comm_query) > 0) {
            $max_comm_row = mysqli_fetch_assoc($max_comm_query);
            // Extract just the numbers (e.g., "COM014" becomes 14)
            $last_comm_number = (int) preg_replace('/[^0-9]/', '', $max_comm_row['committeeID']);
            $next_comm_number = $last_comm_number + 1;
        }

        // Combine back into your sequential string format padded to 3 digits (e.g., COM015)
        $committeeID = "COM" . str_pad($next_comm_number, 3, "0", STR_PAD_LEFT);
        // ----------------------------------------

        // 2. Insert into the committee table using the freshly generated committeeID
        $sql_committee = "INSERT INTO committee (committeeID, memberID, eventID) 
                          VALUES ('$committeeID', '$memberID', '$eventID')";
        
        mysqli_query($link, $sql_committee);
    }
            $show_success_toast = true; 
        } else {
            $msg = "❌ Error Creating Event: " . mysqli_error($link);
            $msg_type = "error";
        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event</title>
    <style>
        .workspace-wrapper { display: flex; flex-direction: column; gap: 20px; width: 100%; position: relative; }
        
        .alert { padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .role-indicator { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; background-color: #fef3c7; color: #d97706; }

        .form-container { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; width: 100%; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 13px; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #334155; box-sizing: border-box; }
        .form-control:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        textarea.form-control { height: 100px; resize: vertical; }

        .form-actions-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        .action-buttons-left { display: flex; gap: 12px; }
        
        .btn { padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; display: inline-block; transition: 0.15s ease; }
        .btn-submit { background-color: #10b981; color: white; }
        .btn-submit:hover { background-color: #059669; }
        .btn-reset { background-color: #64748b; color: white; }
        .btn-reset:hover { background-color: #475569; }
        .btn-cancel { background-color: #ef4444; color: white; }
        .btn-cancel:hover { background-color: #dc2626; }

        .wireframe-success-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 15px 25px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 4px;
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
        }
        .wireframe-tag { font-size: 10px; font-weight: bold; color: #059669; text-transform: uppercase; letter-spacing: 1px; }
        .wireframe-msg-content { font-size: 14px; font-weight: bold; color: #065f46; }

        @keyframes slideInRight {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="workspace-wrapper">
            
            <?php if (!empty($msg)): ?>
                <div class="alert error"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="central-board">
                <div class="board-title">
                    <span>✨ Create New Event</span>
                    <span class="role-indicator">Role: Committee</span>
                </div>

                <form method="POST" action="" class="form-container">
                    
                    <div class="form-group">
                        <label>Hosting Club Assignment</label>
                        <select name="clubID" class="form-control" required>
                            <option value="" disabled <?php echo empty($url_clubID) ? 'selected' : ''; ?>>-- Choose an Existing Club --</option>
                            <?php foreach ($clubs_list as $club): ?>
                                <option value="<?php echo $club['clubID']; ?>" <?php echo ($url_clubID === $club['clubID']) ? 'selected' : ''; ?>>
                                    <?php echo $club['clubID'] . " - " . $club['club_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Event Title</label>
                        <input type="text" name="event_title" class="form-control" placeholder="e.g. Badminton Championship Cup" required>
                    </div>

                    <div class="form-group">
                        <label>Event Description</label>
                        <textarea name="event_desc" class="form-control" placeholder="Provide event description here..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Date & Time</label>
                        <div style="display: flex; gap: 15px;">
                            <input type="date" name="event_date" class="form-control" style="flex: 1;" min="<?php echo date('Y-m-d'); ?>" required>
                            <input type="time" name="event_time" class="form-control" style="flex: 1;" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Venue</label>
                        <select name="event_venue" class="form-control" required>
                            <option value="" disabled selected>-- Select Venue --</option>
                            <option value="Dewan Serbaguna, UMPSA Pekan">Dewan Serbaguna, UMPSA Pekan</option>
                            <option value="Main Auditorium">Main Auditorium</option>
                            <option value="Block W Lab 2">Block W Computer Lab 2</option>
                            <option value="Student Lounge">Student Activity Center</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Maximum Capacity</label>
                        <input type="number" name="event_max_participants" class="form-control" placeholder="e.g. 64" min="1" required>
                    </div>

                    <div class="form-actions-row">
                        <div class="action-buttons-left">
                            <button type="submit" name="submit_event" class="btn btn-submit">Submit</button>
                            <button type="reset" class="btn btn-reset">Reset</button>
                        </div>
                        <div>
                            <a href="manage_events.php" class="btn btn-cancel">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($show_success_toast): ?>
        <div class="wireframe-success-toast" id="successToast">
            <span class="wireframe-tag">&lt;&lt;message&gt;&gt;</span>
            <span class="wireframe-msg-content">Event Created Successfully</span>
        </div>
        
        <script>
            setTimeout(function() {
                var toast = document.getElementById('successToast');
                if (toast) {
                    toast.style.transition = "opacity 0.5s ease";
                    toast.style.opacity = "0";
                    setTimeout(function() { toast.remove(); }, 500);
                }
            }, 4000);
    <?php endif; ?>

</body>
</html>