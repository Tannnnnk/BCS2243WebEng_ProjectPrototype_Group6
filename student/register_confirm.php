<?php
require_once 'student_login_materials.php';

if (!isset($_GET['eventID']) || empty($_GET['eventID'])) {
    header("Location: browse_event.php");
    exit();
}

$eventID = mysqli_real_escape_string($link, $_GET['eventID']);

// 1. Fetch Event Details AND calculate remaining slots
$query = "
    SELECT e.*, 
    (e.event_max_participants - (SELECT COUNT(*) FROM eventregistration WHERE eventID = e.eventID)) as remaining_slots
    FROM events e 
    WHERE e.eventID = '$eventID'
";
$event_res = mysqli_query($link, $query);
$event = mysqli_fetch_assoc($event_res);

// Process Final Registration
if (isset($_POST['confirm_final'])) {
    $today = date('Y-m-d');
    
    // 1. Calculate how many are currently 'Confirmed'
    $count_res = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration WHERE eventID = '$eventID' AND registration_status = 'Confirmed'");
    $count_row = mysqli_fetch_assoc($count_res);
    $current_confirmed = (int)$count_row['total'];
    
    // 2. Fetch max capacity
    $max_capacity = (int)$event['event_max_participants'];
    
    // 3. Determine Status: If current < max, they get a slot, otherwise they go to 'Waiting'
    $status = ($current_confirmed < $max_capacity) ? 'Confirmed' : 'Waiting';
    
    // 4. Insert with calculated status
    // Using ON DUPLICATE KEY UPDATE handles cases where a user might accidentally double-click
    $insert_sql = "INSERT INTO eventregistration (userID, eventID, registration_date, registration_status) 
                   VALUES ('$userID', '$eventID', '$today', '$status')
                   ON DUPLICATE KEY UPDATE registration_status=registration_status";
    
    if (mysqli_query($link, $insert_sql)) {
        header("Location: student_my_participation.php");
        exit;
    } else {
        echo "Database Error: " . mysqli_error($link);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Registration</title>
    <style>
        .workspace-wrapper { display: flex; flex-direction: column; gap: 20px; width: 100%; font-family: sans-serif; }
        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); max-width: 600px; margin: 20px auto; }
        .board-title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; border: none; cursor: pointer; display: inline-block; }
        .btn-register { background-color: #10b981; color: white; }
        .btn-cancel { background-color: #e2e8f0; color: #475569; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    
    <div class="content-area">
        <div class="workspace-wrapper">
            <div class="central-board">
                <div class="board-title">Events Details</div>
                
                <p><strong>Event:</strong> <?php echo htmlspecialchars($event['event_title']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['event_desc']); ?></p>
                
                <div class="info-grid">
                    <p><strong>Date:</strong> <?php echo $event['event_date']; ?></p>
                    <p><strong>Time:</strong> <?php echo $event['event_time']; ?></p>
                    <p><strong>Venue:</strong> <?php echo $event['event_venue']; ?></p>
                    <p><strong>Remaining Slots:</strong> <?php echo $event['remaining_slots']; ?></p>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                
                <form method="POST">
                    <p><input type="checkbox" required> I confirm my registration for this event.</p>
                    <button type="submit" name="confirm_final" class="btn btn-register">Register Event</button>
                    <a href="browse_event.php" class="btn btn-cancel">Back</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>