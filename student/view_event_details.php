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

if (!isset($_GET['id'])) {
    header("Location: manage_events.php");
    exit();
}

$eventID = mysqli_real_escape_string($link, $_GET['id']);

$userID = $_SESSION['userID'];
$username = isset($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Student';
$role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Student'; 

// --- DB QUERY: FETCH GREETINGS STRINGS ---
$photo_path = "";
$stu_name = $username; 
$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);
if ($result_profile && $row = mysqli_fetch_assoc($result_profile)) {
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
}

//clubid
$query = "SELECT e.*, m.clubID 
          FROM events e 
          INNER JOIN membership m ON m.userID = '$userID' 
          WHERE e.eventID = '$eventID'";
$result = mysqli_query($link, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>alert('Error: Event record could not be found.'); window.location.href='manage_events.php';</script>";
    exit();
}

$event = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Specifications - #<?php echo $event['eventID']; ?></title>
    <style>
        .details-wrapper { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; max-width: 650px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); font-family: system-ui, sans-serif; }
        .details-header { font-size: 18px; font-weight: bold; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; }
        .details-row { margin-bottom: 18px; }
        .details-label { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .details-value { font-size: 15px; color: #334155; }
        .desc-box { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 60px; line-height: 1.5; }
        .btn-back { display: inline-block; background-color: #475569; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="details-wrapper">
            <div class="details-header">👁️ Full Event Specifications</div>
            
            <div class="details-row">
                <div class="details-label">Event ID Reference</div>
                <div class="details-value"><code>#EV-<?php echo $event['eventID']; ?></code></div>
            </div>

            <div class="details-row">
                <div class="details-label">Assigned Club ID</div>
                <div class="details-value"><span style="background-color: #e2e8f0; color: #334155; padding: 3px 8px; border-radius: 4px; font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($event['clubID']); ?></span></div>
            </div>

            <div class="details-row">
                <div class="details-label">Event Title</div>
                <div class="details-value" style="font-size: 18px; font-weight: bold; color: #0f172a;"><?php echo htmlspecialchars($event['event_title']); ?></div>
            </div>

            <div class="details-row">
                <div class="details-label">Description Details</div>
                <div class="details-value desc-box"><?php echo nl2br(htmlspecialchars($event['event_desc'])); ?></div>
            </div>

            <div class="details-row">
                <div class="details-label">Date & Time Schedule</div>
                <div class="details-value">📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?> &nbsp;|&nbsp; 🕒 <?php echo date('h:i A', strtotime($event['event_time'])); ?></div>
            </div>

            <div class="details-row">
                <div class="details-label">Venue Location</div>
                <div class="details-value">📍 <?php echo htmlspecialchars($event['event_venue']); ?></div>
            </div>

            <div class="details-row">
                <div class="details-label">Maximum Occupancy Capacity</div>
                <div class="details-value">👥 Up to <strong><?php echo $event['event_max_participants']; ?></strong> members registered slots</div>
            </div>

            <a href="manage_events.php" class="btn-back">⬅️ Return to Workspace</a>
        </div>
    </div>

</body>
</html>
