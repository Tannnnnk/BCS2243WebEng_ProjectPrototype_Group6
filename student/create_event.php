<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'student_login_materials.php';

$show_success_toast = false; 
$msg = "";
$msg_type = "";

// 1. Fetch clubs where the user has committee privileges
$my_clubs = [];
$sql_my_clubs = "SELECT c.clubID, c.club_name 
                 FROM club c 
                 JOIN membership m ON c.clubID = m.clubID 
                 WHERE m.userID = '$userID' AND m.roleID <= 'R08'";
$res_my_clubs = mysqli_query($link, $sql_my_clubs);

while ($row = mysqli_fetch_assoc($res_my_clubs)) {
    $my_clubs[] = $row;
}

if (empty($my_clubs)) {
    echo "<script>alert('Access Denied: You must be a committee member to create events.'); window.location.href='event_directory.php';</script>";
    exit();
}

// 2. Process Submission
if (isset($_POST['submit_event'])) {
    $clubID = mysqli_real_escape_string($link, $_POST['clubID']);
    
    // Security check: Verify user is a committee member for the selected club
    $verify_auth = mysqli_query($link, "SELECT * FROM membership WHERE userID = '$userID' AND clubID = '$clubID' AND roleID <= 'R08'");
    
    if (mysqli_num_rows($verify_auth) == 0) {
        $msg = "❌ Error: Unauthorized club selection.";
        $msg_type = "error";
    } else {
        // Generate Event ID
        $id_check = mysqli_query($link, "SELECT eventID FROM events ORDER BY eventID DESC LIMIT 1");
        $eventID = (mysqli_num_rows($id_check) > 0) ? "E" . ((int)substr(mysqli_fetch_assoc($id_check)['eventID'], 1) + 1) : "E101";

        $event_title = mysqli_real_escape_string($link, $_POST['event_title']);
        $event_desc  = mysqli_real_escape_string($link, $_POST['event_desc']);
        $event_date  = mysqli_real_escape_string($link, $_POST['event_date']);
        $event_time  = mysqli_real_escape_string($link, $_POST['event_time']);
        $event_venue = mysqli_real_escape_string($link, $_POST['event_venue']);
        $event_max_participants = intval($_POST['event_max_participants']);

        // QR Setup
        require_once '../phpqrcode/qrlib.php';
        $storage_folder = "qrcodes/";
        if (!is_dir($storage_folder)) mkdir($storage_folder, 0777, true);
        $attendance_qr = $storage_folder . $eventID . "_qr.png";
        QRcode::png("http://localhost/BCS2243WebEng_ProjectPrototype_Group6/attendance_form.php?eventID=" . $eventID, $attendance_qr, QR_ECLEVEL_L, 10, 2);

        // Insert into Events
        $sql_insert = "INSERT INTO events (eventID, event_title, event_desc, event_date, event_time, event_venue, event_max_participants, attendance_qr) 
                       VALUES ('$eventID', '$event_title', '$event_desc', '$event_date', '$event_time', '$event_venue', $event_max_participants, '$attendance_qr')";
        
        if (mysqli_query($link, $sql_insert)) {
            $memberID = mysqli_fetch_assoc($verify_auth)['memberID'];
            $max_comm = mysqli_query($link, "SELECT committeeID FROM committee WHERE committeeID LIKE 'COM%' ORDER BY committeeID DESC LIMIT 1");
            $next_num = ($max_comm && mysqli_num_rows($max_comm) > 0) ? (int)preg_replace('/[^0-9]/', '', mysqli_fetch_assoc($max_comm)['committeeID']) + 1 : 1;
            $committeeID = "COM" . str_pad($next_num, 3, "0", STR_PAD_LEFT);

            mysqli_query($link, "INSERT INTO committee (committeeID, memberID, eventID) VALUES ('$committeeID', '$memberID', '$eventID')");
            $show_success_toast = true;
        } else {
            $msg = "❌ Error: " . mysqli_error($link);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Event</title>
    <style>
        /* Keeping your existing styles */
        .central-board { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; max-width: 700px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .btn-row { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: bold; }
        .btn-submit { background: #10b981; }
        .btn-reset { background: #64748b; }
        .btn-cancel { background: #ef4444; text-decoration: none; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="central-board">
            <?php if ($show_success_toast) echo "<div style='background:#ecfdf5; color:#065f46; padding:10px; margin-bottom:20px; border-radius:6px;'>✅ Event Created Successfully!</div>"; ?>
            <?php if (!empty($msg)) echo "<div style='background:#fee2e2; color:#991b1b; padding:10px; margin-bottom:20px; border-radius:6px;'>$msg</div>"; ?>
            
            <h2>✨ Create New Event</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Hosting Club</label>
                    <?php if (count($my_clubs) == 1): ?>
                        <input type="text" class="form-control" value="<?php echo $my_clubs[0]['club_name']; ?>" disabled>
                        <input type="hidden" name="clubID" value="<?php echo $my_clubs[0]['clubID']; ?>">
                    <?php else: ?>
                        <select name="clubID" class="form-control" required>
                            <?php foreach ($my_clubs as $club): ?>
                                <option value="<?php echo $club['clubID']; ?>"><?php echo $club['club_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="form-group"><label>Event Title</label><input type="text" name="event_title" class="form-control" required></div>
                <div class="form-group"><label>Description</label><textarea name="event_desc" class="form-control" required></textarea></div>
                <div class="form-group"><label>Date</label><input type="date" name="event_date" class="form-control" required></div>
                <div class="form-group"><label>Time</label><input type="time" name="event_time" class="form-control" required></div>
                <div class="form-group">
                    <label>Venue</label>
                    <select name="event_venue" class="form-control" required>
                        <option value="Dewan Serbaguna, UMPSA Pekan">Dewan Serbaguna, UMPSA Pekan</option>
                        <option value="Main Auditorium">Main Auditorium</option>
                        <option value="Block W Lab 2">Block W Computer Lab 2</option>
                        <option value="Student Lounge">Student Activity Center</option>
                    </select>
                </div>
                <div class="form-group"><label>Capacity</label><input type="number" name="event_max_participants" class="form-control" required></div>
                
                <div class="btn-row">
                    <button type="submit" name="submit_event" class="btn btn-submit">Submit</button>
                    <button type="reset" class="btn btn-reset">Reset</button>
                    <a href="manage_events.php" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>